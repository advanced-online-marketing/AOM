<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\Taboola;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Db;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Services\DatabaseHelperService;

class MarketingPerformanceSubTables extends \Piwik\Plugins\AOM\Platforms\MarketingPerformanceSubTables
{
    public static $SUB_TABLE_ID_CAMPAIGNS = 'Campaigns';
    public static $SUB_TABLE_ID_SITES = 'Sites';

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
            self::$SUB_TABLE_ID_SITES,
        ];
    }

    /**
     * @param DataTable $table
     * @param array $summaryRow
     * @param string $startDate
     * @param string $endDate
     * @param int $idSite
     * @param string $id An arbitrary identifier of a specific platform element (e.g. a campaign or a site)
     * @return array
     * @throws \Exception
     */
    public function getCampaigns(DataTable $table, array $summaryRow, $startDate, $endDate, $idSite, $id)
    {
        // TODO: Use "id" in "platform_data" of aom_visits instead for merging?!

        // Imported data (data like impressions is not available in aom_visits table!)
        $importedData = Db::fetchAssoc(
            'SELECT CONCAT(\'C\', campaign_id) AS campaignId, campaign, ROUND(sum(cost), 2) as cost, '
                . 'SUM(clicks) as clicks, SUM(impressions) as impressions '
                . 'FROM ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_TABOOLA) . ' '
                . 'WHERE idsite = ? AND date >= ? AND date <= ? '
                . 'GROUP BY campaignId',
            [
                $idSite,
                $startDate,
                $endDate,
            ]
        );

        // TODO: This will have bad performance when there's lots of data (use platform_key or something else in future)
        $aomVisits = Db::fetchAssoc(
            'SELECT '
                . '(CASE WHEN (LOCATE(\'campaignId\', platform_data) > 0) '
                . 'THEN CONCAT(\'C\', SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'campaignId\', platform_data)+CHAR_LENGTH(\'campaignId\')+2),\',"\',1))'
                . 'ELSE CONCAT(\'C\', SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'campaign_id\', platform_data)+CHAR_LENGTH(\'campaign_id\')+3),\'"\',1))'
                . 'END) AS campaignId, '
                . 'COUNT(*) AS visits, COUNT(DISTINCT(piwik_idvisitor)) AS unique_visitors, SUM(conversions) AS conversions, SUM(revenue) AS revenue '
                . 'FROM ' . Common::prefixTable('aom_visits') . ' '
                . 'WHERE idsite = ? AND channel = ? AND date_website_timezone >= ? AND date_website_timezone <= ? '
                . 'GROUP BY campaignId',
            [
                $idSite,
                AOM::PLATFORM_TABOOLA,
                $startDate,
                $endDate,
            ]
        );

        // Merge data based on campaignId
        foreach (array_merge_recursive($importedData, $aomVisits) as $data) {

            // We might have visits that we identified as coming from this platform but that we could not merge
            if (!isset($data['campaign'])) {
                $data['campaign'] = 'unknown (Taboola identified but not merged)';  // TODO: Add translation
            }

            // Add to DataTable
            $table->addRowFromArray([
                Row::COLUMNS => $this->getColumns($data['campaign'], $data, $idSite),
                Row::DATATABLE_ASSOCIATED => (isset($data['campaignId'])
                    ? 'Taboola_Sites_' . str_replace('C', '', $data['campaignId'][0])
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
     * @param string $id An arbitrary identifier of a specific platform element (e.g. a campaign or a site)
     * @return array
     * @throws \Exception
     */
    public function getSites(DataTable $table, array $summaryRow, $startDate, $endDate, $idSite, $id)
    {
        // TODO: Use "id" in "platform_data" of aom_visits instead for merging?!

        // Imported data (data like impressions is not available in aom_visits table!)
        $siteData = Db::fetchAssoc(
            'SELECT site_id AS siteId, site, ROUND(sum(cost), 2) as cost, SUM(clicks) as clicks, '
                . 'SUM(impressions) as impressions '
                . 'FROM ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_TABOOLA) . ' '
                . 'WHERE idsite = ? AND date >= ? AND date <= ? AND campaign_id = ?'
                . 'GROUP BY siteId',
            [
                $idSite,
                $startDate,
                $endDate,
                $id,
            ]
        );

        // TODO: This will have bad performance when there's lots of data (use platform_key or something else in future)
        $aomVisits = Db::fetchAssoc(
            'SELECT '
                . '(CASE WHEN (LOCATE(\'siteId\', platform_data) > 0) '
                . 'THEN CONCAT(SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'siteId\', platform_data)+CHAR_LENGTH(\'siteId\')+2),\',"\',1))'
                . 'ELSE CONCAT(SUBSTRING_INDEX(SUBSTR(platform_data, LOCATE(\'site_id\', platform_data)+CHAR_LENGTH(\'site_id\')+3),\'"\',1))'
                . 'END) AS siteId, '
                . 'COUNT(*) AS visits, COUNT(DISTINCT(piwik_idvisitor)) AS unique_visitors, SUM(conversions) AS conversions, SUM(revenue) AS revenue '
                . 'FROM ' . Common::prefixTable('aom_visits') . ' '
                . 'WHERE idsite = ? AND channel = ? AND date_website_timezone >= ? AND date_website_timezone <= ? AND (platform_data LIKE ? OR platform_data LIKE ?) '
                . 'GROUP BY siteId',
            [
                $idSite,
                AOM::PLATFORM_TABOOLA,
                $startDate,
                $endDate,
                '%"campaignId":"' . $id . '"%',
                '%"campaign_id":"' . $id . '"%',
            ]
        );

        // Merge data based on siteId
        foreach (array_merge_recursive($siteData, $aomVisits) as $data) {

            // Add to DataTable
            $table->addRowFromArray([
                Row::COLUMNS => $this->getColumns($data['site'], $data, $idSite),
            ]);

            // Add to summary
            $summaryRow = $this->addToSummary($summaryRow, $data);
        }

        return [$table, $summaryRow];
    }
}
