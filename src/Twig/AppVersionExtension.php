<?php

namespace App\Twig;

use Composer\InstalledVersions;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Exposes the application version ("version" of composer.json) as the app_version Twig global.
 */
final class AppVersionExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return ['app_version' => InstalledVersions::getRootPackage()['pretty_version']];
    }
}
