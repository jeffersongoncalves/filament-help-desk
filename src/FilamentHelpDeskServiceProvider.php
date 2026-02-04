<?php

namespace JeffersonGoncalves\FilamentHelpDesk;

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
        $this->registerDepartmentOperatorsRelationship();
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
