<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

/**
 * Provides reusable Filament infolist schemas for ticket detail views.
 *
 * This trait defines static methods that return arrays of Filament infolist
 * entries, ensuring consistent detail views across User, Operator, and
 * Admin panels.
 */
trait HasTicketInfolist
{
    /**
     * Get the infolist schema for displaying ticket details.
     *
     * @param  bool  $showApplication  When true, includes the originating application
     *                                 entry — shown only for a ticket that carries an
     *                                 app key.
     * @param  bool  $forApi  When true, drops the relation entries an API-hydrated
     *                        ticket cannot resolve.
     * @return array<int, Component>
     */
    public static function getTicketInfolistSchema(bool $showApplication = false, bool $forApi = false): array
    {
        return [
            Section::make(__('filament-help-desk::filament-help-desk.sections.ticket_details'))
                ->schema([
                    TextEntry::make('reference_number')
                        ->label(__('filament-help-desk::filament-help-desk.fields.reference_number')),

                    TextEntry::make('title')
                        ->label(__('filament-help-desk::filament-help-desk.fields.title')),

                    TextEntry::make('status')
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

                    TextEntry::make('priority')
                        ->label(__('filament-help-desk::filament-help-desk.fields.priority'))
                        ->badge()
                        ->color(fn (TicketPriority $state): string => match ($state) {
                            TicketPriority::Low => 'gray',
                            TicketPriority::Medium => 'info',
                            TicketPriority::High => 'warning',
                            TicketPriority::Urgent => 'danger',
                        })
                        ->formatStateUsing(fn (TicketPriority $state): string => $state->label()),

                    // All three are relations, and the show response carries
                    // none of them: the department and category arrive as ids,
                    // and assignment is an operator concern a satellite is not
                    // shown. Reading any of them on an API-hydrated ticket
                    // throws, so they are left out rather than rendered empty.
                    // Resolving the names would cost a request each per view.
                    // Left out entirely rather than hidden: a hidden entry is
                    // still an entry whose state may be resolved, and reading
                    // any of these on an API-hydrated ticket throws.
                    ...($forApi ? [] : [
                        TextEntry::make('department.name')
                            ->label(__('filament-help-desk::filament-help-desk.fields.department')),

                        TextEntry::make('category.name')
                            ->label(__('filament-help-desk::filament-help-desk.fields.category'))
                            ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.na')),

                        TextEntry::make('assignedTo.name')
                            ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
                            ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.unassigned')),
                    ]),

                    TextEntry::make('requester_name')
                        ->label(__('filament-help-desk::filament-help-desk.fields.requester')),

                    // Only the panels that serve several applications ask for
                    // this, and only tickets that came from an identified
                    // application carry a key, so it also stays hidden on a
                    // single-application install.
                    TextEntry::make('app_name')
                        ->label(__('filament-help-desk::filament-help-desk.fields.application'))
                        ->visible(fn (Ticket $record): bool => $showApplication && filled($record->app_key)),

                    TextEntry::make('created_at')
                        ->label(__('filament-help-desk::filament-help-desk.fields.created_at'))
                        ->dateTime(),

                    TextEntry::make('closed_at')
                        ->label(__('filament-help-desk::filament-help-desk.fields.closed_at'))
                        ->dateTime()
                        ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.na')),

                    TextEntry::make('description')
                        ->label(__('filament-help-desk::filament-help-desk.fields.description'))
                        ->html()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}
