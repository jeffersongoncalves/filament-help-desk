<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

/**
 * Provides reusable Filament table columns and filters for ticket listings.
 *
 * This trait defines static methods that return arrays of Filament table
 * columns and filters, ensuring consistent table layouts across User,
 * Operator, and Admin panels.
 */
trait HasTicketTable
{
    /**
     * Get the table columns for ticket listings.
     *
     * @param  bool  $showUser  When true, includes the requester (user) column.
     * @param  bool  $showApplication  When true, includes the originating application
     *                                 column — provided any ticket carries an app key.
     * @param  bool  $forApi  When true, drops what the API transport cannot serve:
     *                        the relation columns, and sorting by a column the
     *                        central application will not sort on.
     * @return array<int, Column>
     */
    public static function getTicketTableColumns(bool $showUser = true, bool $showApplication = false, bool $forApi = false): array
    {
        $columns = [
            // Searchable on both transports: the API searches the title and
            // the reference number, which is exactly this pair. Sortable only
            // where a query can sort — Ticket::SORTABLE has neither.
            TextColumn::make('reference_number')
                ->label(__('filament-help-desk::filament-help-desk.fields.reference_number'))
                ->searchable()
                ->sortable(! $forApi),

            TextColumn::make('title')
                ->label(__('filament-help-desk::filament-help-desk.fields.title'))
                ->searchable()
                ->sortable(! $forApi)
                ->limit(50),

            TextColumn::make('status')
                ->label(__('filament-help-desk::filament-help-desk.fields.status'))
                ->badge()
                ->color(fn (TicketStatus $state): string => match ($state) {
                    TicketStatus::Open => 'info',
                    TicketStatus::Pending => 'warning',
                    TicketStatus::InProgress => 'primary',
                    TicketStatus::OnHold => 'gray',
                    TicketStatus::Resolved => 'success',
                    TicketStatus::Closed => 'danger',
                })
                ->formatStateUsing(fn (TicketStatus $state): string => $state->label()),

            TextColumn::make('priority')
                ->label(__('filament-help-desk::filament-help-desk.fields.priority'))
                ->badge()
                ->color(fn (TicketPriority $state): string => match ($state) {
                    TicketPriority::Low => 'gray',
                    TicketPriority::Medium => 'info',
                    TicketPriority::High => 'warning',
                    TicketPriority::Urgent => 'danger',
                })
                ->formatStateUsing(fn (TicketPriority $state): string => $state->label()),

        ];

        // Both are relations, and an API-hydrated ticket throws for a relation
        // the response did not carry. The department is not in the payload and
        // assignment is withheld from satellites on purpose, so neither is
        // rendered empty — it is left out.
        if (! $forApi) {
            $columns[] = TextColumn::make('department.name')
                ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                ->sortable();

            $columns[] = TextColumn::make('assignedTo.name')
                ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
                ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.unassigned'));
        }

        if ($showUser) {
            $columns[] = TextColumn::make('requester_name')
                ->label(__('filament-help-desk::filament-help-desk.fields.requester'));
        }

        if ($showApplication && static::getTicketApplicationOptions() !== []) {
            $columns[] = TextColumn::make('app_name')
                ->label(__('filament-help-desk::filament-help-desk.fields.application'))
                ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.na'));
        }

        $columns[] = TextColumn::make('created_at')
            ->label(__('filament-help-desk::filament-help-desk.fields.created_at'))
            ->dateTime()
            ->sortable();

        return $columns;
    }

    /**
     * Get the table filters for ticket listings.
     *
     * @param  bool  $showApplication  When true, includes the originating application
     *                                 filter — provided any ticket carries an app key.
     * @param  bool  $forApi  When true, keeps only the filters the API accepts.
     * @return array<int, BaseFilter>
     */
    public static function getTicketTableFilters(bool $showApplication = false, bool $forApi = false): array
    {
        $filters = [
            SelectFilter::make('status')
                ->label(__('filament-help-desk::filament-help-desk.fields.status'))
                ->options(
                    collect(TicketStatus::cases())
                        ->mapWithKeys(fn (TicketStatus $status): array => [
                            $status->value => $status->label(),
                        ])
                        ->toArray()
                ),

            SelectFilter::make('priority')
                ->label(__('filament-help-desk::filament-help-desk.fields.priority'))
                ->options(
                    collect(TicketPriority::cases())
                        ->mapWithKeys(fn (TicketPriority $priority): array => [
                            $priority->value => $priority->label(),
                        ])
                        ->toArray()
                ),

        ];

        // GET tickets narrows by status and priority, and nothing else. A
        // department filter would have to run over the page already fetched,
        // which filters 25 rows out of 300 while looking like it filtered all
        // of them; soft deletes are not exposed to a satellite at all.
        if (! $forApi) {
            $filters[] = SelectFilter::make('department_id')
                ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                ->relationship('department', 'name');

            $filters[] = TrashedFilter::make();
        }

        if ($showApplication && ($applications = static::getTicketApplicationOptions()) !== []) {
            $filters[] = SelectFilter::make('app_key')
                ->label(__('filament-help-desk::filament-help-desk.fields.application'))
                ->options($applications);
        }

        return $filters;
    }

    /**
     * The applications that have opened a ticket, keyed by their app key and
     * labelled with the name each ticket carries. Empty on a single-application
     * install, which is what hides the column and the filter there.
     *
     * @return array<string, string>
     */
    protected static function getTicketApplicationOptions(): array
    {
        return Ticket::query()
            ->whereNotNull('app_key')
            ->distinct()
            ->orderBy('app_key')
            ->pluck('app_key')
            ->mapWithKeys(function ($key): array {
                $key = (string) $key;

                // The label travels with the ticket, since the central
                // application has no configuration describing the others.
                $ticket = Ticket::query()
                    ->where('app_key', $key)
                    ->latest('id')
                    ->first(['id', 'app_key', 'metadata']);

                return [$key => $ticket->app_name ?? $key];
            })
            ->all();
    }
}
