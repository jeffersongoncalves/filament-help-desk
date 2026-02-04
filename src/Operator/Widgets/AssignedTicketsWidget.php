<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class AssignedTicketsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getTableHeading(): string
    {
        return __('filament-help-desk::filament-help-desk.widgets.my_assigned_tickets');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()
                    ->where('assigned_to_type', get_class(auth()->user()))
                    ->where('assigned_to_id', auth()->id())
                    ->open()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label(__('filament-help-desk::filament-help-desk.fields.reference_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('filament-help-desk::filament-help-desk.fields.title'))
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
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

                Tables\Columns\TextColumn::make('priority')
                    ->label(__('filament-help-desk::filament-help-desk.fields.priority'))
                    ->badge()
                    ->color(fn (TicketPriority $state): string => match ($state) {
                        TicketPriority::Low => 'gray',
                        TicketPriority::Medium => 'info',
                        TicketPriority::High => 'warning',
                        TicketPriority::Urgent => 'danger',
                    })
                    ->formatStateUsing(fn (TicketPriority $state): string => $state->label()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-help-desk::filament-help-desk.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('filament-help-desk::filament-help-desk.actions.view_ticket'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
