<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources;

use BackedEnum;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketForm;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketInfolist;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketTable;
use JeffersonGoncalves\FilamentHelpDesk\Driver;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;
use JeffersonGoncalves\HelpDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;
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

    public static function getSlug(?Panel $panel = null): string
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
        $table = $table
            ->columns(static::getTicketTableColumns(showUser: false, forApi: Driver::isApi()))
            ->filters(static::getTicketTableFilters(forApi: Driver::isApi()))
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon(Heroicon::OutlinedTicket)
            ->emptyStateHeading(__('filament-help-desk::filament-help-desk.empty_states.user_heading'))
            ->emptyStateDescription(__('filament-help-desk::filament-help-desk.empty_states.user_description'))
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('filament-help-desk::filament-help-desk.empty_states.user_action')),
            ]);

        if (! Driver::isApi()) {
            return $table->modifyQueryUsing(function (Builder $query): Builder {
                $user = Filament::auth()->user();

                return $query
                    ->where('user_type', $user->getMorphClass())
                    ->where('user_id', $user->getAuthIdentifier());
            });
        }

        // A satellite has no tickets table to query, so the table is fed from
        // the repository instead. Filament hands the page, the page size, the
        // search term, the sort and the active filters to this closure and
        // takes a paginator back — which is exactly the shape forActor()
        // returns, so nothing here pages or filters in memory.
        return $table->records(fn (
            int|string $page,
            int|string $recordsPerPage,
            ?string $search,
            ?string $sortColumn,
            ?string $sortDirection,
            ?array $filters,
        ): LengthAwarePaginator => HelpDesk::tickets()->forActor(
            user: Filament::auth()->user(),
            perPage: (int) $recordsPerPage,
            page: (int) $page,
            status: $filters['status']['value'] ?? null,
            priority: $filters['priority']['value'] ?? null,
            search: $search,
            sort: $sortColumn,
            direction: $sortDirection ?? 'desc',
        ));
    }

    /**
     * Resolve the record behind `/tickets/{uuid}`.
     *
     * Route model binding is a query, and a satellite has nothing to query.
     * The repository throws when nothing matches — including for a ticket that
     * belongs to another user or another application, which the central
     * application refuses without saying which — and Filament turns the null
     * into a 404 from there.
     */
    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        if (! Driver::isApi()) {
            return parent::resolveRecordRouteBinding($key, $modifyQuery);
        }

        try {
            return HelpDesk::tickets()->findByUuid((string) $key);
        } catch (TicketNotFoundException) {
            return null;
        }
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(null)
            ->schema(static::getTicketInfolistSchema(forApi: Driver::isApi()));
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-help-desk.user.resource') !== null;
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
