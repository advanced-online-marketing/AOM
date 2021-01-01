<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\FacebookAds;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Db;
use Piwik\Metrics\Formatter;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Services\DatabaseHelperService;

class MarketingPerformanceSubTables extends \Piwik\Plugins\AOM\Platforms\MarketingPerformanceSubTables
{
    public static $SUB_TABLE_ID_CAMPAIGNS = 'Campaigns';
    public static $SUB_TABLE_ID_AD_SET = 'AdSets';

    /**
     * Returns the name of the first level sub table
     *
     * @return string
     */
    public static function getMainSubTableId()
    {
        return self::$SUB_TABLE_ID_CAMPAIGNS;
    }

    /**
     * Returns the names of all supported sub tables
     *
     * @return string[]
     */
    public static function getSubTableIds()
    {
        return [
            self::$SUB_TABLE_ID_CAMPAIGNS,
            self::$SUB_TABLE_ID_AD_SET,
        ];
    }

    /**
     * @param DataTable $table
     * @param array $summaryRow
     * @param string $startDate
     * @param string $endDate
     * @param int $idSite
     * @param string $id An arbitrary identifier of a specific platform element (e.g. a campaign or an ad group)
     * @return array
     * @throws \Exception
     */
    public function getCampaigns(DataTable $table, array $summaryRow, $startDate, $endDate, $idSite, $id)
    {
        // Imported data (data like impressions is not available in aom_visits table!)
        $importedData = Db::fetchAssoc(
            'SELECT CONCAT(\'C\', campaign_id) AS campaignId, campaign_name as campaign, ROUND(sum(cost), 2) as cost, '
                . 'SUM(clicks) as clicks, SUM(impressions) as impressions '
                . 'FROM ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_FACEBOOK_ADS) . ' '
                . 'WHERE idsite = ? AND date >= ? AND date <= ? '
                . 'GROUP BY campaignId',
            [
                $idSite,
                $startDate,
                $endDate,
            ]
        );


        $aomVisits = Db::fetchAssoc(
            'SELECT '
                . '(CASE WHEN (LOCATE(\'campaignId\', platform_data) > 0) '
                . 'THEN CONCAT(\'C\', SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'campaignId\', platform_data)+CHAR_LENGTH(\'campaignId\')+3),\'"\',1))'
                . 'ELSE CONCAT(\'C\', SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'campaign_id\', platform_data)+CHAR_LENGTH(\'campaign_id\')+3),\'"\',1))'
                . 'END) AS campaignId, '
                . 'COUNT(*) AS visits, COUNT(DISTINCT(piwik_idvisitor)) AS unique_visitors, SUM(conversions) AS conversions, SUM(revenue) AS revenue '
                . 'FROM ' . Common::prefixTable('aom_visits') . ' '
                . 'WHERE idsite = ? AND channel = ? AND date_website_timezone >= ? AND date_website_timezone <= ? '
                . 'GROUP BY campaignId',
            [
                $idSite,
                AOM::PLATFORM_FACEBOOK_ADS,
                $startDate,
                $endDate,
            ]
        );


        // Merge data based on campaignId
        foreach (array_merge_recursive($importedData, $aomVisits) as $data) {

            // We might have visits that we identified as coming from this platform but that we could not merge
            if (!isset($data['campaign'])) {
                $data['campaign'] = 'unknown (Facebook identified but not merged)';  // TODO: Add translation
            }

            // Add to DataTable
            $table->addRowFromArray([
                Row::COLUMNS => $this->getColumns($data['campaign'], $data, $idSite),
                Row::DATATABLE_ASSOCIATED => (isset($data['campaignId'])
                    ? 'FacebookAds_AdSets_' . str_replace('C', '', $data['campaignId'][0])
                    : null),
            ]);

            // Add to summary
            $summaryRow = $this->addToSummary($summaryRow, $data);
        }

        return [$table, $summaryRow];
    }
    
    /**
     * @param DataTable $table
     * @param array $summaryRow
     * @param string $startDate
     * @param string $endDate
     * @param int $idSite
     * @param string $id An arbitrary identifier of a specific platform element (e.g. a campaign or an ad group)
     * @return array
     * @throws \Exception
     */
    public function getAdSets(DataTable $table, array $summaryRow, $startDate, $endDate, $idSite, $id)
    {
        // Imported data (data like impressions is not available in aom_visits table!)
        $adGroupIdData = Db::fetchAssoc(
            'SELECT CONCAT(\'AG\', adset_id) AS adGroupId, adset_name AS adGroup, ROUND(sum(cost), 2) as cost, '
            . 'SUM(clicks) as clicks, SUM(impressions) as impressions '
            . 'FROM ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_FACEBOOK_ADS) . ' '
            . 'WHERE idsite = ? AND date >= ? AND date <= ? AND campaign_id = ? '
            . 'GROUP BY adset_id',
            [
                $idSite,
                $startDate,
                $endDate,
                $id,
            ]
        );

        $aomVisits = Db::fetchAssoc(
            'SELECT '
                . '(CASE WHEN (LOCATE(\'adsetId\', platform_data) > 0) '
                . 'THEN CONCAT(\'AG\', SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'adsetId\', platform_data)+CHAR_LENGTH(\'adSetId\')+3),\'"\',1))'
                . 'ELSE CONCAT(\'AG\', SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'adset_id\', platform_data)+CHAR_LENGTH(\'adset_id\')+3),\'"\',1))'
                . 'END) AS adSetId, '
                . 'COUNT(*) AS visits, COUNT(DISTINCT(piwik_idvisitor)) AS unique_visitors, SUM(conversions) AS conversions, SUM(revenue) AS revenue '
                . 'FROM ' . Common::prefixTable('aom_visits') . ' '
                . 'WHERE idsite = ? AND channel = ? AND date_website_timezone >= ? AND date_website_timezone <= ? AND (platform_data LIKE ? OR platform_data LIKE ?) '
                . 'GROUP BY adSetId',
            [
                $idSite,
                AOM::PLATFORM_FACEBOOK_ADS,
                $startDate,
                $endDate,
                '%"campaignId":"' . $id . '"%',
                '%"campaign_id":"' . $id . '"%',
            ]
        );

        // Merge data based on adGroupId
        foreach (array_merge_recursive($adGroupIdData, $aomVisits) as $data) {

            // Add to DataTable
            $table->addRowFromArray([
                Row::COLUMNS => $this->getColumns($data['adGroup'], $data, $idSite),
//                Row::DATATABLE_ASSOCIATED => 'Bing_Keyword_' . $data['campaignId'],
            ]);

            // Add to summary
            $summaryRow = $this->addToSummary($summaryRow, $data);
        }

        return [$table, $summaryRow];
    }
}
