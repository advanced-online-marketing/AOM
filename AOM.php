<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Piwik\Common;
use Piwik\Config;
use Piwik\Db;
use Piwik\Plugins\AOM\Exceptions\SiteNotFoundException;
use Piwik\Plugins\AOM\Platforms\ImporterInterface;
use Piwik\Plugins\AOM\Platforms\MergerInterface;
use Piwik\Plugins\AOM\Platforms\PlatformInterface;
use Piwik\Plugins\AOM\Services\DatabaseHelperService;
use Psr\Log\LoggerInterface;

class AOM extends \Piwik\Plugin
{
    /**
     * @var LoggerInterface
     */
    private static $logger;

    const PLATFORM_AD_WORDS = 'AdWords';
    const PLATFORM_BING = 'Bing';
    const PLATFORM_CRITEO = 'Criteo';
    const PLATFORM_INDIVIDUAL_CAMPAIGNS = 'IndividualCampaigns';
    const PLATFORM_FACEBOOK_ADS = 'FacebookAds';
    const PLATFORM_TABOOLA = 'Taboola';

    /**
     * Returns all supported platforms.
     *
     * @return array
     */
    public static function getPlatforms()
    {
        return [
            self::PLATFORM_AD_WORDS,
            self::PLATFORM_BING,
            self::PLATFORM_CRITEO,
            self::PLATFORM_FACEBOOK_ADS,
            self::PLATFORM_TABOOLA,

            // Order matters, as visits should only be checked for individual campaigns when there is no other match!
            self::PLATFORM_INDIVIDUAL_CAMPAIGNS,
        ];
    }

    /**
     * @param bool|string $pluginName
     */
    public function __construct($pluginName = false)
    {
        // Add composer dependencies
        require_once PIWIK_INCLUDE_PATH . '/plugins/AOM/vendor/autoload.php';

        // We use our own loggers
        // TODO: Use another file when we are running tests?!
        // TODO: Disable logging to console when running tests!
        // TODO: Allow to configure path and log-level (for every logger)?!
        $format = '%level_name% [%datetime%]: %message% %context% %extra%';


        self::$logger = new Logger('aom');
        if(isset(Config::getInstance()->AOM['log_file'])) {
            $fileStreamHandler = new StreamHandler(Config::getInstance()->AOM['log_file'], Logger::DEBUG);
            $fileStreamHandler->setFormatter(new LineFormatter($format . "\n", null, true, true));
            self::$logger->pushHandler($fileStreamHandler);
        }

        $consoleStreamHandler = new StreamHandler('php://stdout', Logger::DEBUG);
        self::$logger->pushHandler($consoleStreamHandler);

        parent::__construct($pluginName);
    }

    /**
     * Installs the plugin.
     * We must use install() instead of activate() to make integration tests working.
     *
     * @throws \Exception
     */
    public function install()
    {
        foreach (self::getPlatforms() as $platform) {
            $platform = self::getPlatformInstance($platform);
            $platform->installPlugin();
        }

        // We need an auto incrementing key on this table to have a pointer on which conversions we already processed
        DatabaseHelperService::addColumn(
            'ALTER TABLE ' . Common::prefixTable('log_conversion')
                . ' ADD COLUMN `idconversion` INT(10) NOT NULL AUTO_INCREMENT UNIQUE FIRST'
        );

        // This table holds all visits (Piwik visits and artificial visits)
        DatabaseHelperService::addTable(
            'CREATE TABLE ' . Common::prefixTable('aom_visits') . ' (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                idsite INTEGER NOT NULL,
                piwik_idvisit INTEGER,
                piwik_idvisitor VARCHAR(100),
                unique_hash VARCHAR(100) NOT NULL,
                first_action_time_utc DATETIME NOT NULL,
                date_website_timezone DATE NOT NULL,
                channel VARCHAR(100),
                campaign_data TEXT,
                platform_key VARCHAR(255),
                platform_data TEXT,
                cost DECIMAL(10,4),
                conversions INTEGER,
                revenue DECIMAL(14,4),
                ts_last_update TIMESTAMP,
                ts_created TIMESTAMP
            )  DEFAULT CHARSET=utf8');

        // Use piwik_idvisit as unique key to avoid race conditions (manually created visits would have null here)
        // Manually created visits must create consistent keys from the same raw data
        DatabaseHelperService::addIndex(
            'CREATE UNIQUE INDEX index_aom_unique_visits ON ' . Common::prefixTable('aom_visits')
                . ' (unique_hash)'
        );

        // Optimize for queries from Merger
        DatabaseHelperService::addIndex(
            'CREATE INDEX index_aom_visits_site_date_channel ON ' . Common::prefixTable('aom_visits')
                . ' (idsite, date_website_timezone, channel)');

        DatabaseHelperService::addIndex(
            'CREATE INDEX index_aom_visits_site_platform_key ON ' . Common::prefixTable('aom_visits')
            . ' (idsite, platform_key)');

        // Optimize for queries from MarketingPerformanceController
        DatabaseHelperService::addIndex(
            'CREATE INDEX index_aom_visits_marketing_performance ON ' . Common::prefixTable('aom_visits')
                . ' (idsite, channel, date_website_timezone)');

        // Optimize for queries in visitor profile
        DatabaseHelperService::addIndex(
            'CREATE INDEX index_aom_visits_visitor_profile ON ' . Common::prefixTable('aom_visits')
                . ' (idsite, piwik_idvisitor)');

        $this->getLogger()->debug('Installed AOM.');
    }

    /**
     * Uninstalls the plugin.
     */
    public function uninstall()
    {
        foreach (self::getPlatforms() as $platform) {
            $platform = self::getPlatformInstance($platform);
            $platform->uninstallPlugin();
        }

        $this->getLogger()->debug('Uninstalled AOM.');
    }

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return [
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
        ];
    }

    /**
     * Return list of plug-in specific JavaScript files to be imported by the asset manager.
     *
     * @see \Piwik\AssetManager
     */
    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/AOM/javascripts/AOM.js';

        // Platforms might add additional JavaScripts
        foreach (AOM::getPlatforms() as $platformName) {
            /** @var PlatformInterface $platform */
            $platform = AOM::getPlatformInstance($platformName);

            // We load all platform's JS files independent of whether the platform is active or not to avoid issues
            // with Piwik's internal JS cache.

            // If this file exists, it is automatically loaded by naming convention.
            if (file_exists('plugins/AOM/Platforms/' . $platformName . '/javascripts/' . $platformName . '.js')) {
                $jsFiles[] = 'plugins/AOM/Platforms/' . $platformName . '/javascripts/' . $platformName . '.js';
            }

            foreach ($platform->getJsFiles() as $file) {
                $jsFiles[] = $file;
            }
        }
    }

    /**
     * This logger writes to aom.log and to the console.
     *
     * @return LoggerInterface
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    /**
     * Returns the required instance of a platform.
     *
     * @param string $platform
     * @param null|string $class
     * @return PlatformInterface|ImporterInterface|MergerInterface    // TODO: Return interfaces instead of abstract
     * @throws \Exception
     */
    public static function getPlatformInstance($platform, $class = null)
    {
        // Validate arguments
        if (!in_array($platform, AOM::getPlatforms())) {
            throw new \Exception('Platform "' . $platform . '" not supported.');
        }
        if (!in_array($class, [null, 'Importer', 'Merger'])) {
            throw new \Exception('Class "' . $class . '" not supported. Must be either null, "Importer" or "Merger".');
        }

        $className = 'Piwik\\Plugins\\AOM\\Platforms\\' . $platform . '\\' . (null === $class ? $platform : $class);

        return new $className(self::$logger);
    }

    /**
     * Converts a local datetime string (Y-m-d H:i:s) into UTC and returns it as a string (Y-m-d H:i:s).
     *
     * @param string $localDateTime
     * @param string $localTimeZone
     * @return string
     */
    public static function convertLocalDateTimeToUTC($localDateTime, $localTimeZone)
    {
        $date = new \DateTime($localDateTime, new \DateTimeZone($localTimeZone));
        $date->setTimezone(new \DateTimeZone('UTC'));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Converts a UTC datetime string (Y-m-d H:i:s) into website's local time and returns it as a string (Y-m-d H:i:s).
     *
     * @param string $dateTime
     * @param int $idsite
     * @return string
     * @throws SiteNotFoundException
     */
    public static function convertUTCToLocalDateTime($dateTime, $idsite)
    {
        // We cannot use Site::getTimezoneFor($idsite) as this requires view access of the current user which we might
        // not have when matching incoming tracking data
        $timezone = Db::fetchOne(
            'SELECT timezone FROM ' . Common::prefixTable('site') . ' WHERE idsite = ?',
            [$idsite]
        );

        if (!$timezone) {
            throw new SiteNotFoundException('Either site not found or timezone empty for website id ' . $idsite);
        }

        $date = new \DateTime($dateTime);
        $date->setTimezone(new \DateTimeZone($timezone));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Returns all dates within the period, e.g. ['2015-12-20','2015-12-21']
     *
     * @param string $startDate YYYY-MM-DD
     * @param string $endDate YYYY-MM-DD
     * @return array
     */
    public static function getPeriodAsArrayOfDates($startDate, $endDate)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $invert = $start > $end;

        $dates = [];
        $dates[] = $start->format('Y-m-d');

        while ($start != $end) {
            $start->modify(($invert ? '-' : '+') . '1 day');
            $dates[] = $start->format('Y-m-d');
        }

        return $dates;
    }
}
