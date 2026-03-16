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

        return [
            'my' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.my_tickets'))
                ->icon(Heroicon::OutlinedUser)
                ->badge(
                    Ticket::query()
                        ->where('assigned_to_type', $userType)
                        ->where('assigned_to_id', $userId)
                        ->count()
                )
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('assigned_to_type', $userType)
                    ->where('assigned_to_id', $userId)
                ),

            'unassigned' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.unassigned'))
                ->icon(Heroicon::OutlinedUserMinus)
                ->badge(
                    Ticket::query()
                        ->whereNull('assigned_to_id')
                        ->count()
                )
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('assigned_to_id')
                ),

            'all' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.all'))
                ->icon(Heroicon::OutlinedInboxStack)
                ->badge(Ticket::query()->count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'my';
    }
}
