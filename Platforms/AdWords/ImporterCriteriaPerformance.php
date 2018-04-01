<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
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

class ImporterCriteriaPerformance
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
     * @param AdWordsSession $adWordsSession
     * @param $accountId
     * @param $account
     * @param $date
     */
    public function import(AdWordsSession $adWordsSession, $accountId, $account, $date)
    {
        $reportQuery = 'SELECT AccountDescriptiveName, AccountCurrencyCode, AccountTimeZone, CampaignId, CampaignName, '
            . 'AdGroupId, AdGroupName, Id, Criteria, CriteriaType, AdNetworkType1, AdNetworkType2, AveragePosition, '
            . 'Conversions, QualityScore, CpcBid, Impressions, Clicks, GmailSecondaryClicks, Cost, Date '
            . 'FROM CRITERIA_PERFORMANCE_REPORT WHERE Impressions > 0 DURING '
            . str_replace('-', '', $date) . ','
            . str_replace('-', '', $date);

        $reportDownloader = new ReportDownloader($adWordsSession);
        $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
            $reportQuery, DownloadFormat::XML);

        $xml = simplexml_load_string($reportDownloadResult->getAsString());


        // Matching placements based on the string in the value track param {placement} did not work successfully.
        // This is why we aggregate up all placements of an ad group and merge on that level.
        // TODO: This might be no longer necessary since we can match based on gclid?!
        $consolidatedData = [];
        foreach ($xml->table->row as $row) {
            // Clicks of Google Sponsored Promotions (GSP) are more like more engaged ad views than real visits,
            // i.e. we have to reassign clicks (and therewith recalculate CpC)
            // (see http://marketingland.com/gmail-sponsored-promotions-everything-need-know-succeed-direct-response-gsp-part-1-120938)
            if ($row['gmailClicksToWebsite'] > 0) {
                $this->log(Logger::DEBUG, 'Mapping GSP "' . $row['adGroup'] . '" "gmailClicksToWebsite" to clicks.');
                $row['clicks'] = $row['gmailClicksToWebsite'];
            }

            // TODO: Validate currency and timezone?!
            // TODO: qualityScore, maxCPC, avgPosition?!
            // TODO: Find correct place to log warning, errors, etc. and monitor them!

            // Validation
            if (!in_array(strtolower((string) $row['criteriaType']), AdWords::$criteriaTypes)) {
                var_dump('Criteria type "' . (string) $row['criteriaType'] . '" not supported.');
                continue;
            } else {
                $criteriaType = strtolower((string) $row['criteriaType']);
            }

            if (!in_array((string) $row['networkWithSearchPartners'], array_keys(AdWords::$networks))) {
                var_dump('Network "' . (string) $row['networkWithSearchPartners'] . '" not supported.');
                continue;
            } else {
                $network = AdWords::$networks[(string) $row['networkWithSearchPartners']];
            }

            // Construct the key for aggregation (see AdWords/Merger->buildKeyFromAdData())
            $key = ('d' === $network)
                ? implode('-', [$network, $row['campaignID'], $row['adGroupID']])
                : implode('-', [$network, $row['campaignID'], $row['adGroupID'], $row['keywordID']]);

            if (!array_key_exists($key, $consolidatedData)) {
                $consolidatedData[$key] = [
                    'date' => $row['day'],
                    'account' => $row['account'],
                    'campaignId' => $row['campaignID'],
                    'campaign' => $row['campaign'],
                    'adGroupId' => $row['adGroupID'],
                    'adGroup' => $row['adGroup'],
                    'keywordId' => $row['keywordID'],
                    'keywordPlacement' => $row['keywordPlacement'],
                    'criteriaType' => $criteriaType,
                    'network' => $network,
                    'impressions' => $row['impressions'],
                    'clicks' => $row['clicks'],
                    'cost' => ($row['cost'] / 1000000),
                    'conversions' => $row['conversions'],
                ];
            } else {

                // We must aggregate up all placements of an ad group and merge on that level.

                // These values might be no longer unique.
                if ($consolidatedData[$key]['keywordId'] != $row['keywordID']) {
                    $consolidatedData[$key]['keywordId'] = null;
                }
                if ($consolidatedData[$key]['keywordPlacement'] != $row['keywordPlacement']) {
                    $consolidatedData[$key]['keywordPlacement'] = null;
                }
                if ($consolidatedData[$key]['criteriaType'] != $criteriaType) {
                    $consolidatedData[$key]['criteriaType'] = null;
                }

                // Aggregate
                $consolidatedData[$key]['impressions'] = $consolidatedData[$key]['impressions'] + $row['impressions'];
                $consolidatedData[$key]['clicks'] = $consolidatedData[$key]['clicks'] + $row['clicks'];
                $consolidatedData[$key]['cost'] =  $consolidatedData[$key]['cost'] + ($row['cost'] / 1000000);
                $consolidatedData[$key]['conversions'] = $consolidatedData[$key]['conversions'] + $row['conversions'];
            }
        }

        // Write consolidated data to Piwik's database
        // We'll create a very big INSERT here to improve performance (INSERT INTO a (b,c) VALUES (1,1),(1,2),...)
        $dataToInsert = [];
        foreach ($consolidatedData as $data) {

            $uniqueHash = $account['websiteId'] . '-' . $data['date'] . '-'
                . hash('md5', $data['account'] . $data['campaignId'] . $data['adGroupId'] . $data['keywordId']
                    . $data['keywordPlacement'] . $data['criteriaType'] . $data['network']);

            array_push(
                $dataToInsert,
                $accountId, $account['websiteId'], $data['date'], $data['account'], $data['campaignId'],
                $data['campaign'], $data['adGroupId'], $data['adGroup'], $data['keywordId'], $data['keywordPlacement'],
                $data['criteriaType'], $data['network'], $data['impressions'], $data['clicks'], $data['cost'],
                $data['conversions'], $uniqueHash
            );
        }

        // Setup the placeholders - a fancy way to make the long "(?, ?, ?)..." string
        $columns = ['id_account_internal', 'idsite', 'date', 'account', 'campaign_id', 'campaign', 'ad_group_id',
            'ad_group', 'keyword_id', 'keyword_placement', 'criteria_type', 'network', 'impressions', 'clicks', 'cost',
            'conversions',  'unique_hash, ts_created'];
        $rowPlaces = '(' . implode(', ', array_fill(0, count($columns), '?')) . ', NOW())';
        $allPlaces = implode(', ', array_fill(0, count($consolidatedData), $rowPlaces));

        $result = Db::query(
            'INSERT INTO ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_AD_WORDS)
            . ' (' . implode(', ', $columns) . ') VALUES ' . $allPlaces,
            $dataToInsert
        );

        $this->log(
            Logger::DEBUG,
            'Inserted ' . $result->rowCount() . ' records into '
                . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_AD_WORDS) . '.'
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
