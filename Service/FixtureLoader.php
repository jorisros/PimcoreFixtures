<?php
namespace FixtureBundle\Service;

use Fidry\AliceDataFixtures\LoaderInterface;
use Fidry\AliceDataFixtures\Persistence\PersisterAwareInterface;
use Fidry\AliceDataFixtures\Persistence\PersisterInterface;
use FixtureBundle\Alice\Providers\Assets;
use FixtureBundle\Alice\Processor\DocumentProperties;
use FixtureBundle\Alice\Processor\UserProcessor;
use FixtureBundle\Alice\Processor\WorkspaceProcessor;
use FixtureBundle\Alice\Providers\ObjectReference;

class FixtureLoader
{
    const FIXTURE_FOLDER = PIMCORE_PRIVATE_VAR. '/bundles/FixtureBundle/fixtures';
    const IMAGES_FOLDER  = PIMCORE_PRIVATE_VAR . '/bundles/FixtureBundle/images';

    private static $objects = [];

    /**
     * @var Assets
     */
    private Assets $assetsProvider;
    /**
     * @var ObjectReference
     */
    private ObjectReference $objectReferenceProvider;

    /** @var LoaderInterface */
    private LoaderInterface $loader;

    /** @var PersisterInterface */
    private PersisterInterface $persister;

    /**
     * @param Assets $assetsProvider
     * @param ObjectReference $objectReferenceProvider
     * @param LoaderInterface $loader
     * @param PersisterInterface $persister
     */
    public function __construct(Assets $assetsProvider, ObjectReference $objectReferenceProvider, LoaderInterface $loader, PersisterInterface $persister)
    {
        $this->assetsProvider = $assetsProvider;
        $this->assetsProvider->setAssetsPath(self::IMAGES_FOLDER);

        $this->objectReferenceProvider = $objectReferenceProvider;
        $this->objectReferenceProvider->setObjects(self::$objects);

        $this->persister = $persister;
        $this->loader = $loader;
        if ($this->loader instanceof PersisterAwareInterface) {
            $this->loader->withPersister($this->persister);
        }
    }

    /**
     * @param array|null $specificFiles Array of files in fixtures folder
     * @return array
     */
    public static function getFixturesFiles(?array $specificFiles = []): array
    {
        self::createFolderDependencies([
            self::FIXTURE_FOLDER,
            self::IMAGES_FOLDER
        ]);

        if (is_array($specificFiles) && count($specificFiles) > 0) {
            $fixturesFiles = glob(self::FIXTURE_FOLDER . '/{' . implode(',', $specificFiles) . '}.{yml,php}', GLOB_BRACE);
        } else {
            $fixturesFiles = glob(self::FIXTURE_FOLDER . '/*.{yml,php}',GLOB_BRACE);
        }

        usort($fixturesFiles, function ($a, $b) {
            return strnatcasecmp($a, $b);
        });

        return $fixturesFiles;
    }

    /**
     * @param string[] $fixtureFiles
     */
    public function load(array $fixtureFiles): void
    {
        $basename = '';
        self::$objects[ $basename ] = array_merge(self::$objects,  $this->loader->load($fixtureFiles));
    }

    /**
     * Makes sure all folders are created so glob does not throw any error
     * @param array $folders
     */
    private static function createFolderDependencies($folders)
    {
        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
        }
    }
}
