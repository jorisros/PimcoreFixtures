<?php

declare(strict_types=1);

namespace FixtureBundle;

use FixtureBundle\Installer\Installer;
use Pimcore;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class FixtureBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    protected function getComposerPackageName(): string
    {
        return 'youwe/pimcore-fixtures';
    }

    public function getInstaller(): ?InstallerInterface
    {
        $container = Pimcore::getContainer();
        if ($container === null || !$container->has(Installer::class)) {
            return null;
        }

        return $container->get(Installer::class);
    }
}
