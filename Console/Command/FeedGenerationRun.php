<?php

namespace AccelaSearch\Search\Console\Command;

use AccelaSearch\Search\Cron\FeedGeneration;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FeedGenerationRun extends Command
{

    protected FeedGeneration $feedGeneration;

    public function __construct(
        FeedGeneration $feedGeneration,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->feedGeneration = $feedGeneration;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('accelasearch:generate:feed');
        $this->setDescription('Genera accelasearch feed');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("AccelaSearch generate feed start");
        $this->feedGeneration->generateFeed();
        $output->writeln("AccelaSearch generate feed end");

        return Cli::RETURN_SUCCESS;
    }

}
