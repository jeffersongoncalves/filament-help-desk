<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Resources;

use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketForm;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketInfolist;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketTable;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Services\TicketService;

class TicketResource extends Resource
{
    use HasTicketForm;
    use HasTicketInfolist;
    use HasTicketTable;

    protected static ?string $model = Ticket::class;

    protected static ?string $recordRouteKeyName = 'uuid';

    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-help-desk.operator.navigation_group', 'Help Desk'));
    }

    public static function getNavigationIcon(): ?string
    {
        return config('filament-help-desk.operator.navigation_icon', 'heroicon-o-inbox-stack');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-help-desk.operator.navigation_sort');
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-help-desk.operator.navigation_label')
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
        return config('filament-help-desk.operator.slug', 'tickets');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Filament::auth()->user();

        $count = Ticket::query()
            ->where('assigned_to_type', get_class($user))
            ->where('assigned_to_id', $user->getAuthIdentifier())
            ->open()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getTicketEditFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTicketTableColumns(showUser: true))
            ->filters(static::getTicketTableFilters())
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assign_to_me')
                    ->label(__('filament-help-desk::filament-help-desk.actions.assign_to_me'))
                    ->icon('heroicon-o-user-plus')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        /** @var TicketService $ticketService */
                        $ticketService = app(TicketService::class);
                        $operator = Filament::auth()->user();

                        foreach ($records as $record) {
                            $ticketService->assign($record, $operator, $operator);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title(__('filament-help-desk::filament-help-desk.notifications.ticket_assigned'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('change_status')
                    ->label(__('filament-help-desk::filament-help-desk.actions.change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label(__('filament-help-desk::filament-help-desk.fields.status'))
                            ->options(
                                collect(TicketStatus::cases())
                                    ->mapWithKeys(fn (TicketStatus $status): array => [
                                        $status->value => $status->label(),
                                    ])
                                    ->toArray()
                            )
                            ->required(),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        /** @var TicketService $ticketService */
                        $ticketService = app(TicketService::class);
                        $newStatus = TicketStatus::from($data['status']);
                        $performer = Filament::auth()->user();

                        foreach ($records as $record) {
                            if ($record->status->canTransitionTo($newStatus)) {
                                $ticketService->changeStatus($record, $newStatus, $performer);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title(__('filament-help-desk::filament-help-desk.notifications.status_changed'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('change_priority')
                    ->label(__('filament-help-desk::filament-help-desk.actions.change_priority'))
                    ->icon('heroicon-o-flag')
                    ->form([
                        \Filament\Forms\Components\Select::make('priority')
                            ->label(__('filament-help-desk::filament-help-desk.fields.priority'))
                            ->options(
                                collect(TicketPriority::cases())
                                    ->mapWithKeys(fn (TicketPriority $priority): array => [
                                        $priority->value => $priority->label(),
                                    ])
                                    ->toArray()
                            )
                            ->required(),
                    ])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        /** @var TicketService $ticketService */
                        $ticketService = app(TicketService::class);
                        $performer = Filament::auth()->user();

                        foreach ($records as $record) {
                            $ticketService->update($record, ['priority' => $data['priority']], $performer);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title(__('filament-help-desk::filament-help-desk.notifications.priority_changed'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema(static::getTicketInfolistSchema());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-help-desk.operator.resource') !== null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
