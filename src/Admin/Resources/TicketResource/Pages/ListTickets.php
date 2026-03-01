<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.all'))
                ->badge(Ticket::query()->count()),

            'open' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.open'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Open))
                ->badge(Ticket::query()->byStatus(TicketStatus::Open)->count()),

            'pending' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.pending'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Pending))
                ->badge(Ticket::query()->byStatus(TicketStatus::Pending)->count()),

            'in_progress' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.in_progress'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::InProgress))
                ->badge(Ticket::query()->byStatus(TicketStatus::InProgress)->count()),

            'on_hold' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.on_hold'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::OnHold))
                ->badge(Ticket::query()->byStatus(TicketStatus::OnHold)->count()),

            'resolved' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.resolved'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Resolved))
                ->badge(Ticket::query()->byStatus(TicketStatus::Resolved)->count()),

            'closed' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.closed'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Closed))
                ->badge(Ticket::query()->byStatus(TicketStatus::Closed)->count()),
        ];
    }
}
