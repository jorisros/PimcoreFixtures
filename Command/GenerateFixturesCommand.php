<?php

namespace FixtureBundle\Command;

use FixtureBundle\Repository\FolderRepository;
use FixtureBundle\Service\FixtureLoader;
use FixtureBundle\Service\Generator;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\Folder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Command\Command;

class GenerateFixturesCommand extends AbstractCommand
{

    protected function configure()
    {
        $this->setName('fixtures:generate')
            ->setDescription('Generate yml fixtures');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $helper = $this->getHelper('question');

        $folderRootQuestion = new ChoiceQuestion(
            '<info>Choose root folder?</info>',
            $this->formatFoldersToCommandChoices(),
            0
        );

        $rootFolder = $helper->ask($input, $output, $folderRootQuestion);

        $levelsQuestion = new Question('<info>Choose max levels deep (100): </info>', 100);

        $levels = (int)$helper->ask($input, $output, $levelsQuestion);

        $output->writeln(
            ['<info>',
                'You chose: ',
                'Root folder: <comment>' . $rootFolder . '</comment>',
                'Max level deep: <comment>' . $levels . '</comment>',
                '</info>'
            ]);

        $confirmationQuestion = new ConfirmationQuestion(
            '<info>Continue with this action? (y)</info>'
        );

        if (!$helper->ask($input, $output, $confirmationQuestion)) {
            return Command::FAILURE;
        }

        foreach (glob(FixtureLoader::FIXTURE_FOLDER . '_generated' . DIRECTORY_SEPARATOR . '*.yml') as $file){
            unlink($file);
        }

        $rootId = Folder::getByPath($rootFolder)->getId();

        $generator = new Generator($rootId, $levels);
        $generator->generateFixturesForFolder();
        $output->writeln('<info>Done. Your fixtures are at: "' . FixtureLoader::FIXTURE_FOLDER . '".</info>');

        return Command::SUCCESS;
    }

    private function formatFoldersToCommandChoices()
    {
        $foldersRepo = new FolderRepository();
        $folders = $foldersRepo->getFoldersByQuery();
        $foldersArr = [];
        foreach ($folders as $folder) {
            $foldersArr[] = $folder->getFullPath();
        }

        return $foldersArr;
    }

}
