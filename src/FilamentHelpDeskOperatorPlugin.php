<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Contracts\Plugin;
use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\Exceptions\UnsupportedDriverException;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;

class FilamentHelpDeskOperatorPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-help-desk-operator';
    }

    public function register(Panel $panel): void
    {
        if (Driver::isApi()) {
            throw UnsupportedDriverException::operatorPanel('Operator');
        }

        $panel->resources([
            config('filament-help-desk.operator.resource', TicketResource::class),
        ]);

        $panel->widgets(config('filament-help-desk.operator.widgets', []));
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
