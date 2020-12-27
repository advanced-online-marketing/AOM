<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@gmail.com>
 */

namespace Piwik\Plugins\AOM\Platforms\FacebookAds;

use Exception;
use Piwik\Common;
use Piwik\Db;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Platforms\AbstractMerger;
use Piwik\Plugins\AOM\Platforms\MergerInterface;
use Piwik\Plugins\AOM\Platforms\MergerPlatformDataOfVisit;

class Merger extends AbstractMerger implements MergerInterface
{
    public function merge()
    {
        foreach (AOM::getPeriodAsArrayOfDates($this->startDate, $this->endDate) as $date) {
            $this->mergeDay($date);
        }
        $this->validateMergeResults(AOM::PLATFORM_FACEBOOK_ADS, $date);
    }

    public function mergeDay($date)
    {
        foreach ($this->getPlatformRows(AOM::PLATFORM_FACEBOOK_ADS, $date) as $platformRow) {
            $platformKey = $this->getPlatformKey($platformRow['campaign_id'], $platformRow['adset_id'], $platformRow['ad_id']);
            $platformData = [
                'accountId'  => (string)$platformRow['account_id'],
                'account'    => $platformRow['account_name'],
                'campaignId' => (string)$platformRow['campaign_id'],
                'campaign'   => $platformRow['campaign_name'],
                'adsetId'  => (string)$platformRow['adset_id'],
                'adSet'    => $platformRow['adset_name'],
                'adId'  => $platformRow['ad_id'],
                'ad'    => $platformRow['ad_name'],
            ];

            // Update visit's platform data (including historic records) and publish update events when necessary
            $this->updatePlatformData($platformRow['idsite'], $platformKey, $platformData);

            $this->allocateCostOfPlatformRow(AOM::PLATFORM_FACEBOOK_ADS, $platformRow, $platformKey, $platformData);
        }
    }


    public function getPlatformDataOfVisit($idsite, $date, $idvisit, array $aomAdParams)
    {
        $mergerPlatformDataOfVisit = new MergerPlatformDataOfVisit(AOM::PLATFORM_FACEBOOK_ADS);

        // Make sure that we have the needed parameters
        $missingParams = array_diff(['campaignId', 'adsetId', 'adId',], array_keys($aomAdParams));
        if (count($missingParams)) {
            $this->logger->warning(
                'Could not find ' . implode(', ', $missingParams) . ' in ad params of visit ' . $idvisit
                . ' although platform has been identified as Facebook.'
            );
            return $mergerPlatformDataOfVisit;
        }

        $campaignId = $aomAdParams['campaignId'];
        $adsetId = $aomAdParams['adsetId'];
        $adId = $aomAdParams['adId'];

        $mergerPlatformDataOfVisit->setPlatformData(
            [
                'campaignId' => (string)$campaignId,
                'adsetId'    => (string)$adsetId,
                'adId'       => (string)$adId,
            ]
        );

        $mergerPlatformDataOfVisit->setPlatformKey(
            $this->getPlatformKey($campaignId, $adsetId, $adId)
        );

        // Get the exactly matching platform row
        $platformRow = $this->getExactMatchPlatformRow($idsite, $date, $campaignId, $adsetId, $adId);


        if ($platformRow) { // Exact match
            $mergerPlatformDataOfVisit->setPlatformRowId($platformRow['id']);
        } else {
            $platformRow = $this->getHistoricalMatchPlatformRow($idsite, $campaignId, $adsetId, $adId);
        }

        if ($platformRow) {
            $mergerPlatformDataOfVisit->addPlatformData(
                [
                    'account'   => $platformRow['account_name'],
                    'accountId' => (string)$platformRow['account_id'],
                    'campaign'  => $platformRow['campaign_name'],
                    'adSet'     => $platformRow['adset_name'],
                    'ad'        => $platformRow['ad_name'],
                ]
            );
        }

        return $mergerPlatformDataOfVisit;
    }


    private function getPlatformKey($campaignId, $adsetId, $adId)
    {
        return $campaignId . '-' . $adsetId . '-' . $adId;
    }

    private function getExactMatchPlatformRow($idsite, $date, $campaignId, $adsetId, $adId)
    {
        $result = Db::fetchRow(
            'SELECT id, account_id, account_name, campaign_name, adset_name, ad_name '
            . ' FROM ' . Common::prefixTable('aom_facebookads')
            . ' WHERE idsite = ? AND date = ? AND campaign_id = ? AND adset_id = ? AND ad_id = ?',
            [$idsite, $date, $campaignId, $adsetId, $adId,]
        );

        if ($result) {
            $this->logger->debug(
                'Found exact match platform row ID ' . $result['platformRowId'] . ' in imported Facebook data for visit.'
            );
        } else {
            $this->logger->debug('Could not find exact match in imported Facebook data for visit.', [$idsite, $date, $campaignId, $adsetId, $adId]);
        }

        return $result;
    }

    private function getHistoricalMatchPlatformRow($idsite, $campaignId, $adsetId, $adId)
    {
        $result = Db::fetchRow(
            'SELECT account_id, account_name, campaign_name, adset_name, ad_name '
            . ' FROM ' . Common::prefixTable('aom_facebookads')
            . ' WHERE idsite = ?  AND campaign_id = ? AND adset_id = ? AND ad_id = ?',
            [$idsite, $campaignId, $adsetId, $adId,]
        );

        if ($result) {
            $this->logger->debug('Found historical match in imported Facebook data for visit.');
        } else {
            $this->logger->debug('Could not find historical match in imported Facebook data for visit.');
        }

        return $result;
    }
}
