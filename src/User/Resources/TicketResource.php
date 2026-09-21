<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources;

use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;
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

    public static function getNavigationIcon(): ?string
    {
        return config('filament-help-desk.user.navigation_icon', 'heroicon-o-ticket');
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getTicketFormSchema(isUser: true));
    }

    /**
     * Scope every query a database-driver install runs through this
     * resource — the list, and the single-record lookup behind `/{uuid}`
     * that view/comment both resolve through — to what the authenticated
     * user may see: every ticket from their company when the host
     * application's User model resolves one, otherwise only their own.
     *
     * Fixing it here rather than in `table()`'s `modifyQueryUsing()` also
     * closes the record lookup, which Filament does not otherwise scope by
     * itself — a user who guessed or was sent another company's ticket uuid
     * could previously view and comment on it.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Driver::isApi()) {
            return $query;
        }

        $user = Filament::auth()->user();
        $companyId = $user->company_id ?? null;

        // Same condition `Ticket::scopeForCompany()` applies for a non-null
        // key — written out rather than called, since the parent's return
        // type erases the model generic and PHPStan cannot see the scope.
        if ($companyId !== null) {
            return $query->where('company_id', $companyId);
        }

        return $query
            ->where('user_type', $user->getMorphClass())
            ->where('user_id', $user->getAuthIdentifier());
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns(static::getTicketTableColumns(showUser: false, forApi: Driver::isApi()))
            ->filters(static::getTicketTableFilters(forApi: Driver::isApi()))
            ->defaultSort('created_at', 'desc');

        if (! Driver::isApi()) {
            return $table;
        }

        // A satellite has no tickets table to query. Filament v3 has no
        // data source for a table other than an Eloquent query, so the records
        // come from ListTickets::getTableRecords() instead, straight off the
        // repository — see the note there.
        return $table;
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
    public static function resolveRecordRouteBinding(int|string $key): ?Model
    {
        if (! Driver::isApi()) {
            return parent::resolveRecordRouteBinding($key);
        }

        try {
            return HelpDesk::tickets()->findByUuid((string) $key);
        } catch (TicketNotFoundException) {
            return null;
        }
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema(static::getTicketInfolistSchema(forApi: Driver::isApi()));
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
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
