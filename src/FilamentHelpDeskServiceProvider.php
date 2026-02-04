<?php

namespace JeffersonGoncalves\FilamentHelpDesk;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentHelpDeskServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-help-desk';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }
}
