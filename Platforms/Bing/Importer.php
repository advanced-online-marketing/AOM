<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\Bing;

use Bing\Proxy\ClientProxy;
use Bing\Reporting\AccountThroughAdGroupReportScope;
use Bing\Reporting\Date;
use Bing\Reporting\KeywordPerformanceReportColumn;
use Bing\Reporting\KeywordPerformanceReportRequest;
use Bing\Reporting\KeywordPerformanceReportSort;
use Bing\Reporting\PollGenerateReportRequest;
use Bing\Reporting\ReportAggregation;
use Bing\Reporting\ReportFormat;
use Bing\Reporting\ReportRequestStatusType;
use Bing\Reporting\ReportTime;
use Bing\Reporting\SortOrder;
use Bing\Reporting\SubmitGenerateReportRequest;
use Exception;
use Monolog\Logger;
use Piwik\Db;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Platforms\AbstractImporter;
use Piwik\Plugins\AOM\Platforms\ImporterInterface;
use Piwik\Plugins\AOM\Services\DatabaseHelperService;
use Piwik\Plugins\AOM\SystemSettings;
use SoapFault;

//TODO Replace
set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/plugins/AOM/Platforms/Bing');
include 'ReportingClasses.php';
include 'ClientProxy.php';

class Importer extends AbstractImporter implements ImporterInterface
{
    /**
     * Imports all active accounts day by day
     */
    public function import()
    {
        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        foreach ($configuration[AOM::PLATFORM_BING]['accounts'] as $accountId => $account) {
            if (array_key_exists('active', $account) && true === $account['active']) {
                foreach (AOM::getPeriodAsArrayOfDates($this->startDate, $this->endDate) as $date) {
                    $this->importAccount($accountId, $account, $date);
                }
            } else {
                $this->log(Logger::INFO, 'Skipping inactive account.');
            }
        }
    }

    /**
     * @param string $accountId
     * @param array $account
     * @param string $date
     * @throws Exception
     */
    private function importAccount($accountId, $account, $date)
    {
        $this->log(Logger::INFO, 'Will import Bing account ' . $accountId. ' for date ' . $date . ' now.');
        $this->deleteExistingData(AOM::PLATFORM_BING, $accountId, $account['websiteId'], $date);

        $data = $this->getBingReport($accountId, $account, $date);
        if (!$data) {
            $this->log(Logger::WARNING, 'Importing Bing account ' . $accountId. ' for date ' . $date . ' failed.');
            return;
        }

        $result = simplexml_load_string($data);
        foreach ($result->Table->Row as $row) {
            $date = date_create_from_format('m/j/Y', $row->GregorianDate->attributes()['value']);
            $date = $date->format('Y-m-d');

            $uniqueHash = $account['websiteId'] . '-' . $date . '-'
                . hash('md5', $row->AccountId->attributes()['value'] . $row->CampaignId->attributes()['value']
                    . $row->AdGroupId->attributes()['value'] . $row->KeywordId->attributes()['value']);

            Db::query(
                'INSERT INTO ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_BING)
                    . ' (id_account_internal, idsite, date, account_id, account, campaign_id, campaign, ad_group_id, '
                    . 'ad_group, keyword_id, keyword, impressions, clicks, cost, conversions, unique_hash, ts_created) '
                    . 'VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $accountId,
                    $account['websiteId'],
                    $date,
                    $row->AccountId->attributes()['value'],
                    $row->AccountName->attributes()['value'],
                    $row->CampaignId->attributes()['value'],
                    $row->CampaignName->attributes()['value'],
                    $row->AdGroupId->attributes()['value'],
                    $row->AdGroupName->attributes()['value'],
                    $row->KeywordId->attributes()['value'],
                    $row->Keyword->attributes()['value'],
                    $row->Impressions->attributes()['value'],
                    $row->Clicks->attributes()['value'],
                    $row->Spend->attributes()['value'],
                    $row->Conversions->attributes()['value'],
                    $uniqueHash,
                ]
            );
        }
    }

    /**
     * @param $accountId
     * @param $account
     * @param $date
     * @return bool
     */
    public function getBingReport($accountId, $account, $date)
    {
        // Always refresh access token as it expires after 60m
        if (!$this->refreshAccessToken($accountId, $account)) {
            return false;
        }

        try {
            $proxy = ClientProxy::ConstructWithAccountId(
                "https://api.bingads.microsoft.com/Api/Advertiser/Reporting/V9/ReportingService.svc?singleWsdl",
                null,
                null,
                $account['developerToken'],
                $account['accountId'],
                $account['accessToken']
            );

            // Build a keyword performance report request,
            $report = new KeywordPerformanceReportRequest();

            $report->Format = ReportFormat::Xml;
            $report->ReportName = 'AOM Performance Report';
            $report->ReturnOnlyCompleteData = false;
            $report->Aggregation = ReportAggregation::Daily;

            $report->Scope = new AccountThroughAdGroupReportScope();
            $report->Scope->AccountIds = null;
            $report->Scope->AdGroups = null;
            $report->Scope->Campaigns = null;

            $report->Time = new ReportTime();
            $report->Time->CustomDateRangeStart = new Date();
            $report->Time->CustomDateRangeStart->Month = explode('-', $date)[1];
            $report->Time->CustomDateRangeStart->Day = explode('-', $date)[2];
            $report->Time->CustomDateRangeStart->Year = explode('-', $date)[0];
            $report->Time->CustomDateRangeEnd = new Date();
            $report->Time->CustomDateRangeEnd->Month = explode('-', $date)[1];
            $report->Time->CustomDateRangeEnd->Day = explode('-', $date)[2];
            $report->Time->CustomDateRangeEnd->Year = explode('-', $date)[0];

            $report->Columns = array(
                KeywordPerformanceReportColumn::TimePeriod,
                KeywordPerformanceReportColumn::AccountId,
                KeywordPerformanceReportColumn::AccountName,
                KeywordPerformanceReportColumn::CampaignId,
                KeywordPerformanceReportColumn::CampaignName,
                KeywordPerformanceReportColumn::AdGroupId,
                KeywordPerformanceReportColumn::AdGroupName,
                KeywordPerformanceReportColumn::Keyword,
                KeywordPerformanceReportColumn::KeywordId,
                KeywordPerformanceReportColumn::BidMatchType,
                KeywordPerformanceReportColumn::Clicks,
                KeywordPerformanceReportColumn::Impressions,
                KeywordPerformanceReportColumn::Spend,
                KeywordPerformanceReportColumn::QualityScore,
                KeywordPerformanceReportColumn::Conversions,
            );

            // You may optionally sort by any KeywordPerformanceReportColumn, and optionally
            // specify the maximum number of rows to return in the sorted report.

            $report->Sort = array();
            $keywordPerformanceReportSort = new KeywordPerformanceReportSort();
            $keywordPerformanceReportSort->SortColumn = KeywordPerformanceReportColumn::Clicks;
            $keywordPerformanceReportSort->SortOrder = SortOrder::Ascending;
            $report->Sort[] = $keywordPerformanceReportSort;

            $encodedReport = new \SoapVar(
                $report,
                SOAP_ENC_OBJECT,
                'KeywordPerformanceReportRequest',
                $proxy->GetNamespace()
            );

            $request = new SubmitGenerateReportRequest();
            $request->ReportRequest = $encodedReport;

            $reportRequestId = $proxy->GetService()->SubmitGenerateReport($request)->ReportRequestId;

            $this->log(Logger::INFO, 'Report Request ID is ' . $reportRequestId . '.');


            // This sample polls every 30 seconds up to 5 minutes.
            // In production you may poll the status every 1 to 2 minutes for up to one hour.
            // If the call succeeds, stop polling. If the call or
            // download fails, the call throws a fault.

            $reportRequestStatus = null;
            for ($i = 0; $i < 60; $i++) {
                sleep(3);

                // PollGenerateReport helper method calls the corresponding Bing Ads service operation
                // to get the report request status.

                $reportRequestStatus = $this->pollGenerateReport(
                    $proxy,
                    $reportRequestId
                );

                if ($reportRequestStatus->Status == ReportRequestStatusType::Success ||
                    $reportRequestStatus->Status == ReportRequestStatusType::Error
                ) {
                    break;
                }
            }

            if ($reportRequestStatus != null) {
                if ($reportRequestStatus->Status == ReportRequestStatusType::Success) {
                    $reportDownloadUrl = $reportRequestStatus->ReportDownloadUrl;
                    printf("Downloading from %s\n\n", $reportDownloadUrl);
                    return $this->downloadFile($reportDownloadUrl);
                } else {
                    if ($reportRequestStatus->Status == ReportRequestStatusType::Error) {
                        $this->log(Logger::WARNING, 'The request failed. Try requesting the report later.');
                    } else // Pending
                    {
                        $this->log(Logger::WARNING, 'The request is taking longer than expected.');
                    }
                }
            }

        } catch (SoapFault $e) {

            if ($proxy === null) {
                print 'Proxy is null';
            }

            // Output the last request/response.
            if ($proxy) {
                print "\nLast SOAP request/response:\n";
                print $proxy->GetWsdl() . "\n";
                print $this->formatXmlString($proxy->GetService()->__getLastRequest()) . "\n";
                print $this->formatXmlString($proxy->GetService()->__getLastResponse()) . "\n";
            }

            // Reporting service operations can throw AdApiFaultDetail.

        } catch (Exception $e) {
            if ($e->getPrevious()) {
                ; // Ignore fault exceptions that we already caught.
            } else {
                print $e->getCode() . " " . $e->getMessage() . "\n\n";
                print $e->getTraceAsString() . "\n\n";
            }
        }
    }

    /**
     * TODO: There is a similar method in Bing/Controller.php.
     *
     * @param string $accountId
     * @param array $account
     * @return bool
     */
    private function refreshAccessToken($accountId, &$account)
    {
        $settings = new SystemSettings();

        // Attention: This is oauth20_token.srf!
        $url = 'https://login.live.com/oauth20_token.srf?client_id=' . $account['clientId']. '&client_secret='
            . $account['clientSecret'] . '&grant_type=refresh_token&refresh_token=' . $account['refreshToken']
            . '&redirect_uri=' . urlencode('https://login.live.com/oauth20_desktop.srf');

    
        
        $postUrl =  'https://login.live.com/oauth20_token.srf';   
        		
		$postFields = 'client_id=' . $account['clientId']. '&client_secret='
            . $account['clientSecret'] . '&grant_type=refresh_token&refresh_token=' . $account['refreshToken']
            . '&redirect_uri=' . urlencode('https://login.live.com/oauth20_desktop.srf');
        
        
        $response = Bing::urlPostContents($postUrl, $postFields);

        $data = json_decode($response, true);

        if (!array_key_exists('access_token', $data)) {
            $this->log(Logger::WARNING, 'Refreshing the access token failed.');
            return false;
        }

        $account['accessToken'] = $data['access_token'];

        $configuration = $settings->getConfiguration();
        $configuration[AOM::PLATFORM_BING]['accounts'][$accountId]['accessToken'] = $data['access_token'];
        $settings->setConfiguration($configuration);
    }

    /**
     * @param $proxy
     * @param $reportRequestId
     * @return mixed
     */
    private function pollGenerateReport($proxy, $reportRequestId)
    {
        // Set the request information.

        $request = new PollGenerateReportRequest();
        $request->ReportRequestId = $reportRequestId;

        return $proxy->GetService()->PollGenerateReport($request)->ReportRequestStatus;
    }

    /**
     * @param string $reportDownloadUrl
     * @return false|string
     */
    private function downloadFile($reportDownloadUrl)
    {
        $data = $this->getSslPage($reportDownloadUrl);
        $head = unpack('Vsig/vver/vflag/vmeth/vmodt/vmodd/Vcrc/Vcsize/Vsize/vnamelen/vexlen', substr($data, 0, 30));
        return gzinflate(substr($data, 30 + $head['namelen'] + $head['exlen'], $head['csize']));
    }

    /**
     * @param string $url
     * @return mixed
     * @throws Exception
     */
    private function getSslPage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $result = curl_exec($ch);

        if (false === $result) {
            $this->log(Logger::ERROR, 'Curl error ' . curl_errno($ch) . ': ' . curl_error($ch));
            throw new \Exception('Failed to retrieve report.');
        }
        rewind($verbose);

        curl_close($ch);

        return $result;
    }

    protected function formatXmlString($xml)
    {
        $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
        $token = strtok($xml, "\n");
        $result = '';
        $pad = 0;
        $matches = array();
        while ($token !== false) :
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) :
                $indent = 0;
            elseif (preg_match('/^<\/\w/', $token, $matches)) :
                $pad--;
                $indent = 0;
            elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
                $indent = 1;
            else :
                $indent = 0;
            endif;
            $line = str_pad($token, strlen($token) + $pad, ' ', STR_PAD_LEFT);
            $result .= $line . "\n";
            $token = strtok("\n");
            $pad += $indent;
        endwhile;

        return $result;
    }

    /**
     * Convenience function for shorter logging statements
     *
     * @param string $logLevel
     * @param string $message
     * @param array $additionalContext
     */
    private function log($logLevel, $message, $additionalContext = [])
    {
        $this->logger->log(
            $logLevel,
            $message,
            array_merge(['platform' => AOM::PLATFORM_BING, 'task' => 'import'], $additionalContext)
        );
    }
}
