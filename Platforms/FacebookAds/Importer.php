<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms\FacebookAds;

use DateTime;
use Exception;
use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\Values\AdsInsightsLevelValues;
use Monolog\Logger;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Platforms\AbstractImporter;
use Piwik\Plugins\AOM\Platforms\ImporterInterface;
use Piwik\Plugins\AOM\SystemSettings;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Fields\CampaignFields;


class Importer extends AbstractImporter implements ImporterInterface
{

    /**
     * @var array
     */
    protected $adsInsightsFields = [
        AdsInsightsFields::DATE_START,
        AdsInsightsFields::ACCOUNT_NAME,
        AdsInsightsFields::ACCOUNT_ID,
        AdsInsightsFields::CAMPAIGN_ID,
        AdsInsightsFields::CAMPAIGN_NAME,
        AdsInsightsFields::ADSET_ID,
        AdsInsightsFields::ADSET_NAME,
        AdsInsightsFields::AD_NAME,
        AdsInsightsFields::AD_ID,
        AdsInsightsFields::IMPRESSIONS,
        AdsInsightsFields::CLICKS,
        AdsInsightsFields::INLINE_LINK_CLICKS,
        AdsInsightsFields::SPEND,
    ];

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
     * @param string $accountId
     * @param array $account
     * @param string $date
     * @throws \Exception
     */
    private function importAccount($account, $date)
    {
        $this->log(Logger::INFO, 'Will import FacebookAds account ' . $accountId. ' for date ' . $date . ' now.');

//        var_dump($account);

//        var_dump([$account['clientId'], $account['clientSecret'], $account['accessToken']]);
        $api = Api::init($account['clientId'], $account['clientSecret'], $account['accessToken']);

        $adAccount = new AdAccount($account['accountId'], null, $api);


        foreach ($this->fetchAdsInsights($adAccount, $date) as $insight) {
            var_dump([
$insight->{AdsInsightsFields::ACCOUNT_NAME},
$insight->{AdsInsightsFields::ACCOUNT_ID},
$insight->{AdsInsightsFields::CAMPAIGN_NAME},
$insight->{AdsInsightsFields::CAMPAIGN_ID},
$insight->{AdsInsightsFields::ADSET_NAME},
$insight->{AdsInsightsFields::ADSET_ID},
$insight->{AdsInsightsFields::AD_NAME},
$insight->{AdsInsightsFields::AD_ID},
$insight->{AdsInsightsFields::CLICKS},
$insight->{AdsInsightsFields::IMPRESSIONS},
$insight->{AdsInsightsFields::SPEND},
                ]
            );
            die();
//                $ad = new FacebookAds();
//                $ad
//                    ->setDate(new DateTime($date))
//                    ->setSaasUserId($user->getId())
//                    ->setAccountName($insight->{AdsInsightsFields::ACCOUNT_NAME})
//                    ->setAccountId($insight->{AdsInsightsFields::ACCOUNT_ID})
//                    ->setCampaignName($insight->{AdsInsightsFields::CAMPAIGN_NAME})
//                    ->setCampaignId($insight->{AdsInsightsFields::CAMPAIGN_ID})
//                    ->setAdsetName($insight->{AdsInsightsFields::ADSET_NAME})
//                    ->setAdSetId($insight->{AdsInsightsFields::ADSET_ID})
//                    ->setAdName($insight->{AdsInsightsFields::AD_NAME})
//                    ->setAdId($insight->{AdsInsightsFields::AD_ID})
//                    ->setClicks($insight->{AdsInsightsFields::CLICKS})
//                    ->setImpressions($insight->{AdsInsightsFields::IMPRESSIONS})
//                    ->setCosts($insight->{AdsInsightsFields::SPEND});
//
//                $this->entityManager->persist($ad);
        }



//        $adAccount = new AdAccount($account['accountId']);


        var_dump($adAccount->getData());
        
        $cursor = $adAccount->getCampaigns();

// Loop over objects
        foreach ($cursor as $campaign) {
            echo $campaign->{CampaignFields::NAME}.PHP_EOL;
        }

        



//        $this->deleteExistingData(AOM::PLATFORM_FACEBOOK_ADS, $accountId, $account['websiteId'], $date);

//        throw new Exception('Not implemented');
    }

    /**
     * @param AdAccount $adAccount
     * @param string $date
     * @return \FacebookAds\ApiRequest|\FacebookAds\Cursor|\FacebookAds\Http\ResponseInterface|null
     */
    protected function fetchAdsInsights(AdAccount $adAccount, $date)
    {
        $insights = $adAccount->getInsights($this->adsInsightsFields, [
            'level' => AdsInsightsLevelValues::AD,
            'time_range' => [
                'since' => $date,
                'until' => $date,
            ],
        ]);

        $insights->setUseImplicitFetch(true);

        return $insights;
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
