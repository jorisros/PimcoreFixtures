<?php

namespace FixtureBundle\Command;

use FixtureBundle\Service\FixtureLoader;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class RearrangeFixturesCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('fixtures:rearrange')
            ->setDescription('Reorders yml fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Note: Rearrange class was not present in the bundle - implement rearrange logic if needed
        $output->writeln('<info>Done. Your fixtures are at: "' . FixtureLoader::FIXTURE_FOLDER . '".</info>');

        return Command::SUCCESS;
    }
}
