<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */

namespace Piwik\Plugins\AOM\Services;

use Piwik\Common;
use Piwik\Db;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Exceptions\SiteNotFoundException;
use Piwik\Plugins\AOM\Platforms\MergerInterface;
use Psr\Log\LoggerInterface;

class PiwikVisitService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = (null === $logger ? AOM::getLogger() : $logger);
    }

    /**
     * This method should be called by the EventProcessor command by a cron every minute.
     */
    public function checkForNewVisit()
    {
        // Limit to 500 visits to distribute work (if it has queued up for whatever reason)
        // TODO: Move limits to command/supervisor
        do {
            $visits = $this->getUnprocessedVisits();
            foreach ($visits as $visit) {
                $this->addNewPiwikVisit($visit);
            }
        } while ($visits);
    }

    /**
     * @return array
     */
    private function getUnprocessedVisits()
    {
        $latestProcessedPiwikVisit =
            Db::fetchOne('SELECT MAX(piwik_idvisit) FROM ' . Common::prefixTable('aom_visits'));

        return Db::fetchAll(
            'SELECT v.*, conv(hex(v.idvisitor), 16, 10) AS idvisitor, entry_url.name AS entry_url, '
            . ' entry_name.name AS entry_name '
            . ' FROM ' . Common::prefixTable('log_visit') . ' AS v '

            // Only consider visits of sites that still exist
            . ' JOIN ' . Common::prefixTable('site') . ' AS s '
            . ' ON v.idsite = s.idsite'

            . ' LEFT JOIN ' . Common::prefixTable('log_action') . ' AS entry_name '
            . ' ON v.visit_entry_idaction_name = entry_name.idaction'
            . ' LEFT JOIN ' . Common::prefixTable('log_action') . ' AS entry_url '
            . ' ON v.visit_entry_idaction_url = entry_url.idaction'
            . ' WHERE v.idvisit > ' . ($latestProcessedPiwikVisit > 0 ? $latestProcessedPiwikVisit : 0)
            . ' ORDER BY v.idvisit ASC LIMIT 500'
        );
    }

    /**
     * Adds a Piwik visit to the aom_visits table.
     * Conversions and revenue are added to visits by the checkForNewConversion method.
     *
     * @param array $visit
     * @throws \Exception
     */
    private function addNewPiwikVisit(array $visit)
    {
        $idsite = $visit['idsite'];
        try {
            $date = substr(AOM::convertUTCToLocalDateTime($visit['visit_first_action_time'], $visit['idsite']), 0, 10);
        } catch (SiteNotFoundException $e) {
            $this->logger->debug(
                'Skipped Piwik visit ' . $visit['idvisit'] . ' as site ' . $visit['idsite'] . ' or site\'s timezone '
                    . 'does not exist (the site might have been deleted).'
            );

            return;
        }

        /** @var MergerInterface $platformMerger */
        $platformMerger = $visit['aom_platform'] ? AOM::getPlatformInstance($visit['aom_platform'], 'Merger') : null;

        // When the visit is coming from a platform (including individual campaigns), check if it has an exact match.
        // An exact match is a match with cost data, i.e. the costs of that match need to be redistributed (again).
        $mergerPlatformDataOfVisit = ($visit['aom_platform'] && $visit['aom_ad_params'])
            ? $platformMerger->getPlatformDataOfVisit(
                $idsite,
                $date,
                $visit['idvisit'],
                is_array(@json_decode($visit['aom_ad_params'], true)) ? json_decode($visit['aom_ad_params'], true) : []
            )
            : null;

        $channel = self::determineChannel($visit['aom_platform'], $visit['referer_type']);
        $campaignData = self::getCampaignData($visit);
        $platformData = ($mergerPlatformDataOfVisit ? $mergerPlatformDataOfVisit->getPlatformData() : null);
        Db::query(
            'INSERT INTO ' . Common::prefixTable('aom_visits')
            . ' (idsite, piwik_idvisit, piwik_idvisitor, unique_hash, first_action_time_utc, '
            . ' date_website_timezone, channel, campaign_data, platform_data, platform_key, ts_created, '
            . ' ts_last_update) '
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $idsite,
                $visit['idvisit'],
                $visit['idvisitor'],
                'piwik-visit-' . $visit['idvisit'],
                $visit['visit_first_action_time'],
                $date,
                $channel,
                json_encode($campaignData),
                json_encode($platformData),
                ($mergerPlatformDataOfVisit ? $mergerPlatformDataOfVisit->getPlatformKey() : null),
            ]
        );
        $aomVisitId = Db::fetchOne('SELECT LAST_INSERT_ID()');
        $this->logger->debug(
            'Added Piwik visit ' . $visit['idvisit'] . ' as AOM visit ' . $aomVisitId . ' to aom_visit table.'
        );

        // As this new visit could be directly matched with provided cost, we need to redistribute these cost.
        if ($mergerPlatformDataOfVisit && $mergerPlatformDataOfVisit->getPlatformRowId()) {

            $platformMerger->allocateCostOfPlatformRowId(
                $visit['aom_platform'],
                $mergerPlatformDataOfVisit->getPlatformRowId(),
                $mergerPlatformDataOfVisit->getPlatformKey(),
                $mergerPlatformDataOfVisit->getPlatformData()
            );
        }

        self::postAomVisitAddedOrUpdatedEvent($aomVisitId);
    }

    /**
     * @param string $aomPlatform
     * @param string $refererType
     * @return null|string
     */
    private static function determineChannel($aomPlatform, $refererType)
    {
        if ($aomPlatform) {
            return $aomPlatform;
        } elseif (Common::REFERRER_TYPE_DIRECT_ENTRY == $refererType) {
            return 'direct';
        } elseif (Common::REFERRER_TYPE_SEARCH_ENGINE == $refererType) {
            return 'seo';
        } elseif (Common::REFERRER_TYPE_WEBSITE == $refererType) {
            return 'website';
        } elseif (Common::REFERRER_TYPE_CAMPAIGN == $refererType) {
            return 'campaign';
        }

        return null;
    }

    /**
     * @param array $visit
     * @return array
     */
    private static function getCampaignData(array $visit)
    {
        $campaignData = [];

        foreach ([
                     'referer_name' => 'refererName',
                     'referer_url' => 'refererUrl',
                     'entry_name' => 'entryName',
                     'entry_url' => 'entryUrl',
                     'campaign_name' => 'campaignName',
                     'campaign_keyword' => 'campaignKeyword',
                     'campaign_source' => 'campaignSource',
                     'campaign_medium' => 'campaignMedium',
                     'campaign_content' => 'campaignContent',
                     'campaign_id' => 'campaignId',
                 ] as $key => $camelCaseKey) {
            if (isset($visit[$key])) {
                $campaignData[$camelCaseKey] = $visit[$key];
            }
        }

        return $campaignData;
    }

    /**
     * Post an event that a visit has been added or updated.
     *
     * Other plugins might listen to this event and publish them for example to an external SNS topic.
     * We do not add any visit specific information here; other plugins should gather what they need on their own.
     *
     * TODO: Make sure that this method is called in all necessary places.
     * TODO: Should we add conversion information here (but based on which conversion attribution)?
     *
     * @param int $aomVisitId
     */
    public static function postAomVisitAddedOrUpdatedEvent($aomVisitId)
    {
        Piwik::postEvent(
            'AOM.aomVisitAddedOrUpdated',
            [
                'aomVisitId' => $aomVisitId,
            ]
        );

//        var_dump('Posted AOM.aomVisitAddedOrUpdated for aomVisitId ' . $aomVisitId . '.');
    }

    /**
     * This method should be called by the EventProcessor command by a cron every minute.
     * It detects if new conversion have been created. If so, it adds the conversion-related information to the
     * aom_visits table.
     *
     * TODO: Only consider e-commerce conversions?
     */
    public function checkForNewConversion()
    {
        $c = count($this->getUnprocessedVisits());
        if ($c > 0) {
            $this->logger->info('Skip checking for new conversions as there are still ' . $c . ' unprocessed visits.');
            return;
        }

        do {
            $latestProcessedConversion = Option::get('Plugin_AOM_LatestProcessedConversion');
            if (false === $latestProcessedConversion) {
                $latestProcessedConversion = 0;
            }

            $conversions = Db::fetchAll('SELECT idconversion, idvisit, c.idsite AS idsite, idorder, revenue '
                . ' FROM ' . Common::prefixTable('log_conversion') . ' AS c '

                // Only consider conversions of sites that still exist
                . ' JOIN ' . Common::prefixTable('site') . ' AS s '
                . ' ON c.idsite = s.idsite'

                . ' WHERE idconversion > ' . $latestProcessedConversion
                . ' ORDER BY idconversion ASC LIMIT 100'); // Limit to distribute work (if it has queued up)

            foreach ($conversions as $conversion) {
                // For every single conversion: Increment visit's conversion count and add revenue
                $result = Db::query(
                    'UPDATE ' . Common::prefixTable('aom_visits') . ' SET '
                    . ' conversions = IFNULL(conversions, 0) + 1, revenue = IFNULL(revenue, 0) + ?, '
                    . ' ts_last_update = NOW() '
                    . ' WHERE unique_hash = ?',
                    [
                        $conversion['revenue'],
                        'piwik-visit-' . $conversion['idvisit'],    // We use unique_hash to use an existing index.
                    ]
                );
                if (1 === $result->rowCount()) {
                    $this->logger->debug(
                        'Added conversion ' . $conversion['idconversion'] . ' (' . $conversion['idorder'] . ')'
                        . ' to Piwik visit ' . $conversion['idvisit'] . ' in aom_visits table.'
                    );
                    self::postAomVisitAddedOrUpdatedEvent($conversion['idvisit']);
                } else {
                    $this->logger->error(
                        'Could not add conversion ' . $conversion['idconversion'] . ' (' . $conversion['idorder'] . ') '
                            . 'as visit ' . $conversion['idvisit'] . ' was not found in aom_visits table.'
                    );
                }

                Option::set('Plugin_AOM_LatestProcessedConversion', $conversion['idconversion'], 1);
            }

        } while ($conversions);
    }
}
