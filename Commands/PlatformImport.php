<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\AOM\AOM;
use Piwik\Plugins\AOM\Platforms\PlatformInterface;
use Piwik\Plugins\AOM\SystemSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Example:
 * ./console aom:import --platform=AdWords --startDate=2015-12-20 --endDate=2015-12-20
 * ./console aom:import --platform=AdWords --startDate=2015-12-20 --endDate=2015-12-20 --merge=true
 */
class PlatformImport extends ConsoleCommand
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
        $this->configureAOMImportCommand($this);
    }

    protected function configureAOMImportCommand(ConsoleCommand $command)
    {
        $command->setName('aom:import');
        $command->setDescription('Import an advertising platform\'s data for a specific period.');
        $command->addOption('platform', null, InputOption::VALUE_REQUIRED);
        $command->addOption('startDate', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD');
        $command->addOption('endDate', null, InputOption::VALUE_REQUIRED, 'YYYY-MM-DD');
        $command->addOption('merge', null, InputOption::VALUE_OPTIONAL, 'Merge after import', false);
        $command->addOption('memory-limit', null, InputOption::VALUE_OPTIONAL, 'Forwards the PHP memory_limit value to the PHP CLI command. For example `--memory-limit=2147483648` would result in the process being allowed 2GB of RAM. Default is to not specify this value and use the server\'s own memory_limit value.', $default = '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // We might need a little more RAM than what the server's default memory_limit value is so check if the memory-limit option was specified.
        if($input->getOption('memory-limit')){ // Use what was provided via console/cli option
            ini_set('memory_limit',$input->getOption('memory-limit'));
        }

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
                ['platform' => $input->getOption('platform'), 'task' => 'import']
            );
            return;
        }

        /** @var PlatformInterface $platform */
        $platform = AOM::getPlatformInstance($input->getOption('platform'));
        $platform->import($input->getOption('merge'), $input->getOption('startDate'), $input->getOption('endDate'));

        $this->logger->info(
            $input->getOption('platform') . '-import successful.',
            ['platform' => $input->getOption('platform'), 'task' => 'import']
        );
    }
}
