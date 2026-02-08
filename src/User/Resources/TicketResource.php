<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketForm;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketInfolist;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketTable;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class TicketResource extends Resource
{
    use HasTicketForm;
    use HasTicketInfolist;
    use HasTicketTable;

    protected static ?string $model = Ticket::class;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-help-desk.user.navigation_group', 'Support'));
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return config('filament-help-desk.user.navigation_icon', Heroicon::OutlinedTicket);
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-help-desk.user.navigation_sort');
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-help-desk.user.navigation_label')
            ?? __('filament-help-desk::filament-help-desk.navigation.tickets');
    }

    public static function getModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.navigation.tickets');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.navigation.tickets');
    }

    public static function getSlug(): string
    {
        return config('filament-help-desk.user.slug', 'tickets');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(null)
            ->schema(static::getTicketFormSchema(isUser: true));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTicketTableColumns(showUser: false))
            ->filters(static::getTicketTableFilters())
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = Filament::auth()->user();

                return $query
                    ->where('user_type', get_class($user))
                    ->where('user_id', $user->getAuthIdentifier());
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(null)
            ->schema(static::getTicketInfolistSchema());
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
