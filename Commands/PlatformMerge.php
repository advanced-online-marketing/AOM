<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 */
namespace Piwik\Plugins\AOM\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\SystemSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Example:
 * ./console aom:import --platform=AdWords --startDate=2017-05-12 --endDate=2017-05-12
 * ./console aom:merge --platform=AdWords --startDate=2017-05-12 --endDate=2017-05-12
 */
class PlatformMerge extends ConsoleCommand
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string|null $name
     * @param LoggerInterface|null $logger
     */
    public function __construct($name = null, LoggerInterface $logger = null)
    {
        $this->logger = AOM::getLogger();

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('aom:merge')
            ->addOption('platform', null, InputOption::VALUE_REQUIRED)
            ->addOption('startDate', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD')
            ->addOption('endDate', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD')
            ->setDescription('Merges an advertising platform\'s data for a specific period.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!in_array($input->getOption('platform'), AOM::getPlatforms())) {
            $this->logger->warning('Platform "' . $input->getOption('platform') . '" is not supported.');
            $this->logger->warning('Platform must be one of: ' . implode(', ', AOM::getPlatforms()));
            return;
        }

        // Is platform active?
        $settings = new SystemSettings();
        if (!$settings->{'platform' . $input->getOption('platform') . 'IsActive'}->getValue()) {
            $this->logger->warning(
                'Platform "' . $input->getOption('platform') . '" is not active.',
                ['platform' => $input->getOption('platform'), 'task' => 'merge']
            );
            return;
        }

        // TODO: Validate startDate and endDate as both are required!

        $platform = AOM::getPlatformInstance($input->getOption('platform'));
        $platform->merge($input->getOption('startDate'), $input->getOption('endDate'));

        $this->logger->info(
            $input->getOption('platform') . '-merge successful.',
            ['platform' => $input->getOption('platform'), 'task' => 'merge']
        );
    }
}
