<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    public function getTabs(): array
    {
        $user = Filament::auth()->user();
        $userType = get_class($user);
        $userId = $user->getAuthIdentifier();

        return [
            'my' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.my_tickets'))
                ->icon('heroicon-o-user')
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
                ->icon('heroicon-o-user-minus')
                ->badge(
                    Ticket::query()
                        ->whereNull('assigned_to_id')
                        ->count()
                )
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNull('assigned_to_id')
                ),

            'all' => Tab::make(__('filament-help-desk::filament-help-desk.tabs.all'))
                ->icon('heroicon-o-inbox-stack')
                ->badge(Ticket::query()->count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'my';
    }
}
