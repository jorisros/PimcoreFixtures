<?php

namespace FixtureBundle\Command;

use Doctrine\DBAL\Connection;
use FixtureBundle\Service\FixtureLoader;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends AbstractCommand
{
    public function __construct(
        private readonly FixtureLoader $fixtureLoader,
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('fixtures:load')
            ->setDescription('Imports yml fixtures')
            ->addOption('with-cache', 'c', InputArgument::OPTIONAL, 'Calculates the fingerprint of fixtures and if matches with load sql file instead of looping throw fixtures', false)
            ->addOption('omit-validation', 'ov', InputArgument::OPTIONAL, 'Omits object validation', true)
            ->addOption('check-path-exists', 'cpe', InputArgument::OPTIONAL, 'If true it will check if the path already exists and if yes then it will update the values', false)
            ->addOption('files', 'f', InputArgument::OPTIONAL, 'Comma separated files located at "' . FixtureLoader::FIXTURE_FOLDER . '"');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $withCache = $input->getOption('with-cache');
        $files = $input->getOption('files') ? explode(',', $input->getOption('files')) : null;
        $fixtureFiles = FixtureLoader::getFixturesFiles($files);
        $fingerPrintFilePath = PIMCORE_TEMPORARY_DIRECTORY . '/pimcore_fixtures_cache_' . $this->getSha1FromFixtures($fixtureFiles). '.sql';

        if ($withCache === false || file_exists($fingerPrintFilePath) === false) {
            $steps = $withCache ? count($fixtureFiles) + 1 : count($fixtureFiles);
            $progress = new ProgressBar($output, $steps);
            $progress->setProgressCharacter(' ');
            $progress->setEmptyBarCharacter(' ');
            $progress->setBarCharacter("\xF0\x9F\x8D\xBA"); // Beer
            $progress->setOverwrite(false);
            $progress->start();
            $progress->setFormat(" %current%/%max% [%bar%] <info>%percent:3s%% %elapsed:6s% %memory:6s%\t%message%</info>");

            foreach ($fixtureFiles as $fixtureFile) {
                $progress->setMessage('<comment>Loading</comment>  ' . str_replace(defined('PIMCORE_PRIVATE_VAR') ? PIMCORE_PRIVATE_VAR : PIMCORE_WEBSITE_VAR, '', $fixtureFile));
                $this->fixtureLoader->load([$fixtureFile]);
                $progress->advance();
            }

            if ($withCache === true) {
                $progress->setMessage('<comment>Caching loaded data</comment>');
                $progress->advance();
                $this->cacheFixtures($fingerPrintFilePath);
            }
            $progress->setMessage('');
            $progress->finish();
            $progress->clear();
            $output->writeln('  ');

        } else {
            if (file_exists($fingerPrintFilePath)) {
                $output->writeln(' <info>Loading fixtures from cache</info>');
                $this->loadFromCache($fingerPrintFilePath);
            }
        }

        return Command::SUCCESS;
    }


    private function getSha1FromFixtures($fixtureFiles)
    {
        $sha1sFromContent = '';
        foreach ($fixtureFiles as $fixtureFile) {
            if (is_file($fixtureFile) && is_readable($fixtureFile)) {
                $sha1sFromContent .= sha1_file($fixtureFile);
            }
        }

        return sha1($sha1sFromContent);
    }

    /**
     * @param $destination
     */
    private function cacheFixtures(string $destination): void
    {
        $params = $this->getConnectionParams();
        $temp = $this->getTemporaryCredentialsFile($params);

        $metaData = stream_get_meta_data($temp);
        $tmpFilePath = $metaData['uri'];

        $dumpCommand = join(' ', [
            'mysqldump',
            '--defaults-file=' . $tmpFilePath,
            '--databases ' . $params['dbname'],
            '--port ' . ($params['port'] ?? 3306),
            '--no-autocommit',
            '--single-transaction',
            '> ' . $destination
        ]);

        system($dumpCommand);

        fclose($temp);
    }

    /**
     * @return array{host: string, port: int, dbname: string, user: string, password: string}
     */
    private function getConnectionParams(): array
    {
        $params = $this->connection->getParams();
        if (isset($params['url'])) {
            $parsed = parse_url($params['url']);
            return [
                'host' => $parsed['host'] ?? 'localhost',
                'port' => isset($parsed['port']) ? (int) $parsed['port'] : 3306,
                'dbname' => ltrim($parsed['path'] ?? '', '/'),
                'user' => $parsed['user'] ?? '',
                'password' => $parsed['pass'] ?? '',
            ];
        }
        return [
            'host' => $params['host'] ?? 'localhost',
            'port' => isset($params['port']) ? (int) $params['port'] : 3306,
            'dbname' => $params['dbname'] ?? '',
            'user' => $params['user'] ?? $params['username'] ?? '',
            'password' => $params['password'] ?? '',
        ];
    }

    /**
     * @param array{host: string, user: string, password: string} $params
     * @return resource
     */
    private function getTemporaryCredentialsFile(array $params)
    {
        $temp = tmpfile();
        $credentials = join("\n", [
            '[client]',
            'user = ' . $params['user'],
            'password = ' . $params['password'],
            'host = ' . $params['host']
        ]);
        fwrite($temp, $credentials);

        return $temp;
    }

    private function loadFromCache(string $filePath): void
    {
        $params = $this->getConnectionParams();
        $temp = $this->getTemporaryCredentialsFile($params);

        $metaData = stream_get_meta_data($temp);
        $tmpFilePath = $metaData['uri'];

        $mysqlLoadCommand = join(' ', [
            'mysql',
            '--defaults-file=' . $tmpFilePath,
            '--port ' . ($params['port'] ?? 3306),
            $params['dbname'],
            ' < ' . $filePath
        ]);

        system($mysqlLoadCommand);

        fclose($temp);
    }
}
