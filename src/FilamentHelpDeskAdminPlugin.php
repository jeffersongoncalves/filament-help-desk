<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Contracts\Plugin;
use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource;

class FilamentHelpDeskAdminPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-help-desk-admin';
    }

    public function register(Panel $panel): void
    {
        $resources = config('filament-help-desk.admin.resources', []);

        $panel->resources([
            $resources['ticket'] ?? TicketResource::class,
            $resources['department'] ?? DepartmentResource::class,
            $resources['category'] ?? CategoryResource::class,
            $resources['canned_response'] ?? CannedResponseResource::class,
            $resources['email_channel'] ?? EmailChannelResource::class,
        ]);

        $panel->widgets(config('filament-help-desk.admin.widgets', []));
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
