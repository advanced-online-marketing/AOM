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
use Piwik\Plugins\AOM\Services\PiwikVisitService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Example:
 * ./console aom:process
 */
class EventProcessor extends ConsoleCommand
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
        $this->configureAOMProcessCommand($this);
    }

    // This is reused by another console command
    public static function configureAOMProcessCommand(ConsoleCommand $command)
    {
	    $command->setName('aom:process');
	    $command->setDescription('Processes visits and conversions by updating aom_visits.');
	    $command->addOption('memory-limit', null, InputOption::VALUE_OPTIONAL, 'Forwards the PHP memory_limit value to the PHP CLI command. For example `--memory-limit=2147483648` would result in the process being allowed 2GB of RAM.', $default = '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // We might need a little more RAM
        if($input->getOption('memory-limit')){ // Use what was provided via console/cli option
            ini_set('memory_limit',$input->getOption('memory-limit'));
        }

        $this->logger->info('Starting aom:process run.');

        // TODO: Update docs.
        // TODO: Make sure that this command cannot create race conditions!
        // TODO: Make this command running continuously via Supervisor as preferred method.

        $piwikVisitService = new PiwikVisitService($this->logger);

        // Check if new visits have been created. If so, add this visit to aom_visits table.
        $piwikVisitService->checkForNewVisit();

        // Check if new conversion have been created. If so, increment conversion counter and add revenue of visit.
        $piwikVisitService->checkForNewConversion();

        $this->logger->info('Completed aom:process run.');
    }
}
