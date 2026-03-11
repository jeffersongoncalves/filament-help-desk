<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketForm;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketInfolist;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketTable;
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

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-help-desk.admin.navigation_group', 'Help Desk'));
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-help-desk.admin.navigation_sort');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.navigation.tickets');
    }

    public static function getModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.navigation.tickets');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.navigation.tickets');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        $operatorModel = config('help-desk.models.operator');

        $schema = static::getTicketEditFormSchema();

        $schema[] = Select::make('assigned_to_id')
            ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
            ->options(fn (): array => $operatorModel::query()
                ->pluck('name', 'id')
                ->toArray()
            )
            ->searchable()
            ->preload()
            ->nullable()
            ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.unassigned'));

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTicketTableColumns(showUser: true))
            ->filters(static::getTicketTableFilters())
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('change_status')
                        ->label(__('filament-help-desk::filament-help-desk.actions.change_status'))
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('status')
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
                        ->action(function (Collection $records, array $data): void {
                            /** @var TicketService $ticketService */
                            $ticketService = app(TicketService::class);
                            $performer = Filament::auth()->user();

                            $records->each(function (Ticket $ticket) use ($data, $ticketService, $performer): void {
                                $ticketService->changeStatus(
                                    ticket: $ticket,
                                    newStatus: TicketStatus::from($data['status']),
                                    performer: $performer,
                                );
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('change_priority')
                        ->label(__('filament-help-desk::filament-help-desk.actions.change_priority'))
                        ->icon('heroicon-o-flag')
                        ->form([
                            Select::make('priority')
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
                        ->action(function (Collection $records, array $data): void {
                            /** @var TicketService $ticketService */
                            $ticketService = app(TicketService::class);
                            $performer = Filament::auth()->user();

                            $records->each(function (Ticket $ticket) use ($data, $ticketService, $performer): void {
                                $ticketService->update(
                                    ticket: $ticket,
                                    data: ['priority' => $data['priority']],
                                    performer: $performer,
                                );
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('assign')
                        ->label(__('filament-help-desk::filament-help-desk.actions.assign'))
                        ->icon('heroicon-o-user')
                        ->form([
                            Select::make('assigned_to_id')
                                ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
                                ->options(function (): array {
                                    $operatorModel = config('help-desk.models.operator');

                                    return $operatorModel::query()
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $operatorModel = config('help-desk.models.operator');
                            $operator = $operatorModel::find($data['assigned_to_id']);

                            /** @var TicketService $ticketService */
                            $ticketService = app(TicketService::class);
                            $assignedBy = Filament::auth()->user();

                            $records->each(function (Ticket $ticket) use ($operator, $ticketService, $assignedBy): void {
                                $ticketService->assign(
                                    ticket: $ticket,
                                    operator: $operator,
                                    assignedBy: $assignedBy,
                                );
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema(static::getTicketInfolistSchema());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-help-desk.admin.resources.ticket') !== null;
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
