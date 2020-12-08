<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\FacebookAds;

use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\Values\AdsInsightsLevelValues;
use Monolog\Logger;
use Piwik\Db;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Platforms\AbstractImporter;
use Piwik\Plugins\AOM\Platforms\ImporterInterface;
use Piwik\Plugins\AOM\Services\DatabaseHelperService;
use Piwik\Plugins\AOM\SystemSettings;


class Importer extends AbstractImporter implements ImporterInterface
{
    /**
     * Imports all active accounts day by day
     */
    public function import()
    {
        $settings = new SystemSettings();
        $configuration = $settings->getConfiguration();

        foreach ($configuration[AOM::PLATFORM_FACEBOOK_ADS]['accounts'] as $accountId => $account) {
            if (array_key_exists('active', $account) && true === $account['active']) {
                foreach (AOM::getPeriodAsArrayOfDates($this->startDate, $this->endDate) as $date) {
                    $this->importAccount($account, $date);
                }
            } else {
                $this->log(Logger::INFO, 'Skipping inactive account.');
            }
        }
    }

    /**
     * @param array $account
     * @param string $date
     * @throws \Exception
     */
    private function importAccount($account, $date)
    {
        $accountId= $account['accountId'];
        
        $this->log(Logger::INFO, 'Will import FacebookAds account ' . $account['accountId']. ' for date ' . $date . ' now.');

        $api = Api::init($account['clientId'], $account['clientSecret'], $account['accessToken']);
        $adAccount = new AdAccount('act_'.$accountId, null, $api);
        
        $this->deleteExistingData(AOM::PLATFORM_FACEBOOK_ADS, $accountId, $account['websiteId'], $date);

        $insights = $adAccount->getInsights([
            AdsInsightsFields::DATE_START,
            AdsInsightsFields::ACCOUNT_NAME,
            AdsInsightsFields::CAMPAIGN_ID,
            AdsInsightsFields::CAMPAIGN_NAME,
            AdsInsightsFields::ADSET_ID,
            AdsInsightsFields::ADSET_NAME,
            AdsInsightsFields::AD_NAME,
            AdsInsightsFields::AD_ID,
            AdsInsightsFields::IMPRESSIONS,
            AdsInsightsFields::INLINE_LINK_CLICKS,
            AdsInsightsFields::SPEND,
        ], [
            'level' => AdsInsightsLevelValues::AD,
            'time_range' => [
                'since' => $date,
                'until' => $date,
            ],
        ]);

        foreach ($insights as $insight) {
            Db::query(
                'INSERT INTO ' . DatabaseHelperService::getTableNameByPlatformName(AOM::PLATFORM_FACEBOOK_ADS)
                . ' (id_account_internal, idsite, date, account_id, account_name, campaign_id, campaign_name, '
                . 'adset_id, adset_name, ad_id, ad_name, impressions, clicks, cost, ts_created) '
                . 'VALUE (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $accountId,
                    $account['websiteId'],
                    $insight->getData()[AdsInsightsFields::DATE_START],
                    $accountId,
                    $insight->getData()[AdsInsightsFields::ACCOUNT_NAME],
                    $insight->getData()[AdsInsightsFields::CAMPAIGN_ID],
                    $insight->getData()[AdsInsightsFields::CAMPAIGN_NAME],
                    $insight->getData()[AdsInsightsFields::ADSET_ID],
                    $insight->getData()[AdsInsightsFields::ADSET_NAME],
                    $insight->getData()[AdsInsightsFields::AD_ID],
                    $insight->getData()[AdsInsightsFields::AD_NAME],
                    $insight->getData()[AdsInsightsFields::IMPRESSIONS],
                    $insight->getData()[AdsInsightsFields::INLINE_LINK_CLICKS],
                    $insight->getData()[AdsInsightsFields::SPEND],
                ]
            );
        }
        $this->log(Logger::INFO, 'Imported '.count($insights).' ads from ' . $account['accountId']. ' for date ' . $date . ' now.');
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
            array_merge(['platform' => AOM::PLATFORM_FACEBOOK_ADS, 'task' => 'import'], $additionalContext)
        );
    }
}
