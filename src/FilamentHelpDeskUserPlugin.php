<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Contracts\Plugin;
use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\FilamentHelpDesk\User\Widgets\UserTicketStatsWidget;

class FilamentHelpDeskUserPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-help-desk-user';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            config('filament-help-desk.user.resource', TicketResource::class),
        ]);

        $panel->widgets([
            UserTicketStatsWidget::class,
        ]);
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
