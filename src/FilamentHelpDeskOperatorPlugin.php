<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Contracts\Plugin;
use Filament\Panel;
use JeffersonGoncalves\Filament\Kanban\Pages\KanbanBoard;
use JeffersonGoncalves\FilamentHelpDesk\Exceptions\UnsupportedDriverException;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Pages\TicketsKanbanBoard;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;

class FilamentHelpDeskOperatorPlugin implements Plugin
{
    protected bool $kanbanEnabled = false;

    public function getId(): string
    {
        return 'filament-help-desk-operator';
    }

    /**
     * Registers the optional drag-and-drop Kanban page alongside the ticket
     * table. Requires jeffersongoncalves/filament-kanban — see the README —
     * and is silently skipped if it is not installed, so calling this on a
     * host app without the dependency never breaks panel boot.
     */
    public function kanban(bool $condition = true): static
    {
        $this->kanbanEnabled = $condition;

        return $this;
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

        if ($this->kanbanEnabled && class_exists(KanbanBoard::class)) {
            $panel->pages([
                TicketsKanbanBoard::class,
            ]);
        }
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
