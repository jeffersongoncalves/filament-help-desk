<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Driver;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    /**
     * Where the rows come from on a satellite.
     *
     * Filament v3 builds a table from an Eloquent query and offers no other
     * data source, so the query is bypassed here rather than pointed at a
     * table this application does not have. The page, the page size, the
     * search term, the sort and the active filters are read off the table the
     * same way Filament would, and handed to the repository — which is what
     * pages and narrows, so nothing is filtered in memory over a page that was
     * already fetched.
     */
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        if (! Driver::isApi()) {
            return parent::getTableRecords();
        }

        return $this->cachedTableRecords ??= HelpDesk::tickets()->forActor(
            user: Filament::auth()->user(),
            perPage: (int) $this->getTableRecordsPerPage(),
            page: $this->getPage($this->getTablePaginationPageName()),
            status: $this->tableFilters['status']['value'] ?? null,
            priority: $this->tableFilters['priority']['value'] ?? null,
            search: $this->getTableSearch(),
            sort: $this->getTableSortColumn(),
            direction: $this->getTableSortDirection() ?? 'desc',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('filament-help-desk::filament-help-desk.actions.create_ticket')),
        ];
    }
}
