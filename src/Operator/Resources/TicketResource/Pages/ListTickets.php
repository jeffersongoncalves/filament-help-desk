<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;
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
        $user = Filament::auth()->user();
        $userType = get_class($user);
        $userId = $user->getAuthIdentifier();

        // Single query (conditional aggregation) instead of one COUNT per tab.
        $counts = Ticket::query()
            ->toBase()
            ->selectRaw('count(*) as all_count')
            ->selectRaw('sum(case when assigned_to_type = ? and assigned_to_id = ? then 1 else 0 end) as my_count', [$userType, $userId])
            ->selectRaw('sum(case when assigned_to_id is null then 1 else 0 end) as unassigned_count')
            ->first();

        return [
            'my' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.my_tickets'))
                ->icon(Heroicon::OutlinedUser)
                ->badge((int) ($counts->my_count ?? 0))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('assigned_to_type', $userType)
                    ->where('assigned_to_id', $userId)
                ),

            'unassigned' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.unassigned'))
                ->icon(Heroicon::OutlinedUserMinus)
                ->badge((int) ($counts->unassigned_count ?? 0))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('assigned_to_id')
                ),

            'all' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.all'))
                ->icon(Heroicon::OutlinedInboxStack)
                ->badge((int) ($counts->all_count ?? 0)),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'my';
    }
}
