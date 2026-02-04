<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Contracts\Plugin;
use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\AssignedTicketsWidget;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\TicketsByStatusWidget;

class FilamentHelpDeskOperatorPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-help-desk-operator';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            config('filament-help-desk.operator.resource', TicketResource::class),
        ]);

        $panel->widgets([
            TicketsByStatusWidget::class,
            AssignedTicketsWidget::class,
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
