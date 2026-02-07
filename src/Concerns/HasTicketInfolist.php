<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;

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
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    public static function getTicketInfolistSchema(): array
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

                    TextEntry::make('department.name')
                        ->label(__('filament-help-desk::filament-help-desk.fields.department')),

                    TextEntry::make('category.name')
                        ->label(__('filament-help-desk::filament-help-desk.fields.category'))
                        ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.na')),

                    TextEntry::make('assignedTo.name')
                        ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
                        ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.unassigned')),

                    TextEntry::make('user.name')
                        ->label(__('filament-help-desk::filament-help-desk.fields.requester')),

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
