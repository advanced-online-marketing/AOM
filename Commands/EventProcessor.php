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
        $this
            ->setName('aom:process')
            ->setDescription('Processes visits and conversions by updating aom_visits.')
            ->addOption('visit-batch-size', null, InputOption::VALUE_OPTIONAL, 'Size of the batch of visits to be processed (if queued up). Default is 500.', $default = 500);
            ->addOption('conversion-batch-size', null, InputOption::VALUE_OPTIONAL, 'Size of the batch of visits to be processed (if queued up). Default is 100.', $default = 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // We might need a little more RAM
        ini_set('memory_limit','1024M');

        $this->logger->info('Starting aom:process run.');

        // TODO: Update docs.
        // TODO: Make sure that this command cannot create race conditions!
        // TODO: Make this command running continuously via Supervisor as preferred method.

        $piwikVisitService = new PiwikVisitService($this->logger);

        // Check if new visits have been created. If so, add this visit to aom_visits table.
        $piwikVisitService->checkForNewVisit($input->getOption('visit-batch-size')); // // Use what was provided via console/cli option as the visit batch size/count limit to be processed (default is 500 [set via Piwik command option default & function parameter defaults])

        // Check if new conversion have been created. If so, increment conversion counter and add revenue of visit.
        $piwikVisitService->checkForNewConversion($input->getOption('conversion-batch-size')); // Use what was provided via console/cli option as the conversion batch size/count limit to be processed (default is 100 [set via Piwik command option default & function parameter defaults])

        $this->logger->info('Completed aom:process run.');
    }
}
