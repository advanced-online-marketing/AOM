<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\AdWords;

use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\Reporting\v201802\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201802\ReportDownloader;
use Monolog\Logger;
use Piwik\Db;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Services\DatabaseHelperService;
use Psr\Log\LoggerInterface;

class ImporterClickPerformance
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = (null === $logger ? AOM::getLogger() : $logger);
    }

    /**
     * Imports the AdWords click performance report into adwords_gclid-table.
     * Tries to fix/update ad params of related visits when they are empty.
     *
     * @param AdWordsSession $adWordsSession
     * @param $accountId
     * @param $account
     * @param $date
     */
    public function import(AdWordsSession $adWordsSession, $accountId, $account, $date)
    {
        $reportQuery = 'SELECT AccountDescriptiveName, AdFormat, AdGroupId, AdGroupName, AdNetworkType1, '
            . 'AdNetworkType2, AoiMostSpecificTargetId, CampaignId, CampaignLocationTargetId, CampaignName, ClickType, '
            . 'CreativeId, CriteriaId, CriteriaParameters, Date, Device, ExternalCustomerId, GclId, KeywordMatchType, '
            . 'LopMostSpecificTargetId, Page, Slot, UserListId '
            . 'FROM CLICK_PERFORMANCE_REPORT DURING '
            . str_replace('-', '', $date) . ','
            . str_replace('-', '', $date);

        $reportDownloader = new ReportDownloader($adWordsSession);
        $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
            $reportQuery, DownloadFormat::XML);

        $xml = simplexml_load_string($reportDownloadResult->getAsString());

        // We'll create a very big INSERT here to improve performance (INSERT INTO a (b,c) VALUES (1,1),(1,2),...)
        $dataToInsert = [];

        // Add data to both lookup table and big INSERT statement
        $validRows = 0;
        foreach ($xml->table->row as $row) {

            // Map some values
            if (!in_array((string) $row['networkWithSearchPartners'], array_keys(AdWords::$networks))) {
                $this->log(
                    Logger::ERROR,
                    'Network "' . (string) $row['networkWithSearchPartners'] . '" not supported.'
                );
                continue;
            } else {
                $network = AdWords::$networks[(string) $row['networkWithSearchPartners']];
            }

            // Add data to big INSERT statement
            array_push(
                $dataToInsert,
                $accountId, $account['websiteId'], $row['day'], $row['account'], $row['campaignID'], $row['campaign'],
                $row['adGroupID'], $row['adGroup'], $row['keywordID'], $row['keywordPlacement'], $row['matchType'],
                $row['adID'], $row['adType'], $network, $row['device'], (string) $row['googleClickID']
            );

            $validRows++;
        }

        $this->bulkInsertGclidData($dataToInsert, $validRows);
    }

    /**
     * @param array $dataToInsert
     * @param $validRows
     * @throws \Exception
     */
    private function bulkInsertGclidData(array $dataToInsert, $validRows)
    {
        // Setup the placeholders - a fancy way to make the long "(?, ?, ?)..." string
        $columns = ['id_account_internal', 'idsite', 'date', 'account', 'campaign_id', 'campaign', 'ad_group_id',
            'ad_group', 'keyword_id', 'keyword_placement', 'match_type', 'ad_id', 'ad_type', 'network', 'device',
            'gclid', 'ts_created'];
        $rowPlaces = '(' . implode(', ', array_fill(0, count($columns) - 1, '?')) . ', NOW())';
        $allPlaces = implode(', ', array_fill(0, $validRows, $rowPlaces));

        // In rare cases duplicate keys occur
        $result = Db::query(
            'INSERT IGNORE INTO ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_AD_WORDS) . '_gclid'
            . ' (' . implode(', ', $columns) . ') VALUES ' . $allPlaces,
            $dataToInsert
        );
        $duplicates = $validRows - $result->rowCount();
        if ($duplicates > 0) {
            $this->log(
                Logger::WARNING,
                'Got ' . $duplicates . ' duplicate key' . (1 == $duplicates ? '' : 's') . ' when inserting into '
                . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_AD_WORDS) . '_gclid.'
            );
        }
        if ($duplicates > 10) {
            throw new \Exception(
                'Too many duplicate key errors (' . $duplicates . ') when inserting into '
                . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_AD_WORDS) . '_gclid.'
            );
        }

        $this->log(
            Logger::DEBUG,
            'Inserted ' . $result->rowCount() . ' records into '
            . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_AD_WORDS) . '_gclid.'
        );
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
            array_merge(['platform' => AOM::PLATFORM_AD_WORDS, 'task' => 'import'], $additionalContext)
        );
    }
}
