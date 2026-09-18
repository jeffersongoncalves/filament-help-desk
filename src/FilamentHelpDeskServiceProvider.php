<?php

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use JeffersonGoncalves\FilamentHelpDesk\Contracts\KnowledgeBaseProvider;
use JeffersonGoncalves\HelpDesk\Models\Department;
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

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-help-desk', __DIR__.'/../resources/dist/filament-help-desk.css'),
        ], 'jeffersongoncalves/filament-help-desk');

        $this->registerDepartmentOperatorsRelationship();
        $this->bindKnowledgeBaseProvider();
    }

    /**
     * Bound only when a host application actually configured one, so
     * `app()->bound(KnowledgeBaseProvider::class)` alone tells the deflection
     * card whether it has anything to call — no separate enabled check.
     *
     * Unbinds first: this runs once at boot in production, but a test
     * flipping config and calling it again should not find a binding this
     * same call is about to decide against — the container never removes a
     * binding on its own just because the config that produced it changed.
     */
    protected function bindKnowledgeBaseProvider(): void
    {
        $this->app->offsetUnset(KnowledgeBaseProvider::class);

        $provider = config('filament-help-desk.knowledge_base.provider');

        if (! config('filament-help-desk.knowledge_base.enabled')) {
            return;
        }

        if (! is_string($provider) || ! class_exists($provider)) {
            return;
        }

        if (! is_subclass_of($provider, KnowledgeBaseProvider::class)) {
            return;
        }

        $this->app->bind(KnowledgeBaseProvider::class, $provider);
    }

    protected function registerDepartmentOperatorsRelationship(): void
    {
        Department::resolveRelationUsing('operators', function (Department $department) {
            $operatorModel = config('help-desk.models.operator');

            return $department->morphedByMany(
                $operatorModel,
                'operator',
                'help_desk_department_operator',
                'department_id',
                null,
            )->withPivot('role')->withTimestamps();
        });
    }
}
