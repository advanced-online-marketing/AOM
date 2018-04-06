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
            ->setDescription('Processes visits and conversions by updating aom_visits.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
