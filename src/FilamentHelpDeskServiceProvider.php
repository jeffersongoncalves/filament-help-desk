<?php

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Facades\Filament;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource as AdminTicketResource;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource as OperatorTicketResource;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource as UserTicketResource;
use JeffersonGoncalves\HelpDesk\Models\Department;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;

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
        $this->registerNotificationUrls();
    }

    /**
     * Point help desk notification links at the ticket's view page: operators
     * on the operator panel (or the admin panel without one), requesters on the
     * user panel. An application registering its own resolver in its
     * AppServiceProvider overrides this one, since app providers boot later.
     */
    protected function registerNotificationUrls(): void
    {
        Ticket::resolveNotificationUrlUsing(function (Ticket $ticket, object $notifiable, bool $forOperator): string {
            $targets = $forOperator
                ? [
                    'filament-help-desk-operator' => config('filament-help-desk.operator.resource', OperatorTicketResource::class),
                    'filament-help-desk-admin' => config('filament-help-desk.admin.resources.ticket', AdminTicketResource::class),
                ]
                : ['filament-help-desk-user' => config('filament-help-desk.user.resource', UserTicketResource::class)];

            foreach ($targets as $pluginId => $resource) {
                foreach (Filament::getPanels() as $panel) {
                    if (! $panel->hasPlugin($pluginId)) {
                        continue;
                    }

                    try {
                        return $resource::getUrl('view', ['record' => $ticket], panel: $panel->getId());
                    } catch (Throwable) {
                        // ponytail: a tenant-scoped panel needs a tenant to build the URL; fall back to home rather than fail the email.
                        break;
                    }
                }
            }

            return url('/');
        });
    }

    protected function registerDepartmentOperatorsRelationship(): void
    {
        Department::resolveRelationUsing('operators', function (Department $department) {
            $operatorModel = config('help-desk.models.operator');
            $table = 'help_desk_department_operator';

            // The query runs on the operator's connection, but the pivot lives
            // on the help desk one: point at the help desk database by name.
            // ponytail: works when both databases share one server (MySQL/MariaDB/SQL Server).
            $helpDeskDatabase = $department->getConnection()->getDatabaseName();

            if ($helpDeskDatabase !== (new $operatorModel)->getConnection()->getDatabaseName()) {
                $table = $helpDeskDatabase.'.'.$table;
            }

            return $department->morphedByMany(
                $operatorModel,
                'operator',
                $table,
                'department_id',
                null,
            )->withPivot('role', 'email_notifications')->withTimestamps();
        });
    }
}
