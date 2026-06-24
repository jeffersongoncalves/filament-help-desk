<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament-help-desk::filament-help-desk.actions.create_ticket')),
        ];
    }

    public function getTabs(): array
    {
        // Single grouped aggregate instead of one COUNT query per tab.
        $counts = Ticket::query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = (int) $counts->sum();
        $countFor = fn (TicketStatus $status): int => (int) ($counts[$status->value] ?? 0);

        return [
            'all' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.all'))
                ->badge($total),

            'open' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.open'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Open))
                ->badge($countFor(TicketStatus::Open)),

            'pending' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.pending'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Pending))
                ->badge($countFor(TicketStatus::Pending)),

            'in_progress' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.in_progress'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::InProgress))
                ->badge($countFor(TicketStatus::InProgress)),

            'on_hold' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.on_hold'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::OnHold))
                ->badge($countFor(TicketStatus::OnHold)),

            'resolved' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.resolved'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Resolved))
                ->badge($countFor(TicketStatus::Resolved)),

            'closed' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.closed'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->byStatus(TicketStatus::Closed))
                ->badge($countFor(TicketStatus::Closed)),
        ];
    }
}
