<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;

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
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    public static function getTicketTableColumns(bool $showUser = true): array
    {
        $columns = [
            TextColumn::make('reference_number')
                ->label(__('filament-help-desk::filament-help-desk.fields.reference_number'))
                ->searchable()
                ->sortable(),

            TextColumn::make('title')
                ->label(__('filament-help-desk::filament-help-desk.fields.title'))
                ->searchable()
                ->sortable()
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

            TextColumn::make('department.name')
                ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                ->sortable(),

            TextColumn::make('assignedTo.name')
                ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
                ->sortable()
                ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.unassigned')),
        ];

        if ($showUser) {
            $columns[] = TextColumn::make('user.name')
                ->label(__('filament-help-desk::filament-help-desk.fields.requester'))
                ->sortable();
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
     * @return array<int, \Filament\Tables\Filters\BaseFilter>
     */
    public static function getTicketTableFilters(): array
    {
        return [
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

            SelectFilter::make('department_id')
                ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                ->relationship('department', 'name'),

            TrashedFilter::make(),
        ];
    }
}
