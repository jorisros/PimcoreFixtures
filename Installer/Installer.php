<?php

declare(strict_types=1);

namespace FixtureBundle\Installer;

use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;

class Installer extends SettingsStoreAwareInstaller
{
    /**
     * @var string
     */
    private const FIXTURE_FOLDER = PIMCORE_PRIVATE_VAR . '/bundles/FixtureBundle/fixtures';

    public function install(): void
    {
        if (!is_dir(self::FIXTURE_FOLDER)) {
            mkdir(self::FIXTURE_FOLDER, 0755, true);
            $this->getOutput()->writeln('Created fixtures directory: ' . self::FIXTURE_FOLDER);
        }

        parent::install();
    }

    public function uninstall(): void
    {
        parent::uninstall();
    }
}
