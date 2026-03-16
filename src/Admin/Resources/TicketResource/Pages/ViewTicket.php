<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\InteractsWithTicketComments;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Services\TicketService;

/**
 * @property-read Ticket $record
 * @property Schema $commentForm
 */
class ViewTicket extends ViewRecord
{
    use InteractsWithTicketComments;

    protected static string $resource = TicketResource::class;

    protected string $view = 'filament-help-desk::admin.pages.view-ticket';

    public ?array $commentData = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->commentForm->fill();
    }

    public function commentForm(Schema $schema): Schema
    {
        return $schema
            ->columns(null)
            ->schema([
                RichEditor::make('body')
                    ->label(__('filament-help-desk::filament-help-desk.comments.reply'))
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'orderedList',
                        'bulletList',
                        'blockquote',
                        'codeBlock',
                    ])
                    ->columnSpanFull(),

                Toggle::make('is_internal')
                    ->label(__('filament-help-desk::filament-help-desk.fields.internal_note'))
                    ->helperText(__('filament-help-desk::filament-help-desk.comments.internal_note_help'))
                    ->default(false),

                FileUpload::make('attachments')
                    ->label(__('filament-help-desk::filament-help-desk.fields.attachments'))
                    ->multiple()
                    ->maxFiles(config('help-desk.ticket.max_attachments_per_comment', 5))
                    ->maxSize(config('help-desk.ticket.max_file_size', 10240))
                    ->acceptedFileTypes(
                        collect(config('help-desk.ticket.allowed_extensions', []))
                            ->flatMap(fn (string $ext): array => \Symfony\Component\Mime\MimeTypes::getDefault()->getMimeTypes($ext))
                            ->unique()
                            ->values()
                            ->toArray()
                    )
                    ->disk(config('help-desk.ticket.attachment_disk', 'local'))
                    ->directory(config('help-desk.ticket.attachment_path', 'help-desk/attachments'))
                    ->columnSpanFull(),
            ])
            ->statePath('commentData');
    }

    public function getComments(): Collection
    {
        return $this->getCommentsForTimeline();
    }

    protected function getHeaderActions(): array
    {
        $operatorModel = config('help-desk.models.operator');

        return [
            Actions\Action::make('assign')
                ->label(__('filament-help-desk::filament-help-desk.actions.assign'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('info')
                ->form([
                    Select::make('assigned_to_id')
                        ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
                        ->options(fn (): array => $operatorModel::query()
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $operatorModel = config('help-desk.models.operator');
                    $operator = $operatorModel::find($data['assigned_to_id']);

                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);

                    $ticketService->assign(
                        ticket: $this->record,
                        operator: $operator,
                        assignedBy: Filament::auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.ticket_assigned'))
                        ->success()
                        ->send();

                    $this->record->refresh();
                    $this->dispatch('$refresh');
                }),

            Actions\Action::make('change_status')
                ->label(__('filament-help-desk::filament-help-desk.actions.change_status'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
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
                        ->default(fn (): string => $this->record->status->value)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);

                    $ticketService->changeStatus(
                        ticket: $this->record,
                        newStatus: TicketStatus::from($data['status']),
                        performer: Filament::auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.status_changed'))
                        ->success()
                        ->send();

                    $this->record->refresh();
                    $this->dispatch('$refresh');
                }),

            Actions\Action::make('change_priority')
                ->label(__('filament-help-desk::filament-help-desk.actions.change_priority'))
                ->icon(Heroicon::OutlinedFlag)
                ->color('info')
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
                        ->default(fn (): string => $this->record->priority->value)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);

                    $ticketService->update(
                        ticket: $this->record,
                        data: ['priority' => $data['priority']],
                        performer: Filament::auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.priority_changed'))
                        ->success()
                        ->send();

                    $this->record->refresh();
                    $this->dispatch('$refresh');
                }),

            Actions\Action::make('close')
                ->label(__('filament-help-desk::filament-help-desk.actions.close_ticket'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! in_array($this->record->status, [
                    TicketStatus::Closed,
                    TicketStatus::Resolved,
                ]))
                ->action(function (): void {
                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);

                    $ticketService->close(
                        ticket: $this->record,
                        performer: Filament::auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.ticket_closed'))
                        ->success()
                        ->send();

                    $this->record->refresh();
                    $this->dispatch('$refresh');
                }),

            Actions\Action::make('reopen')
                ->label(__('filament-help-desk::filament-help-desk.actions.reopen_ticket'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, [
                    TicketStatus::Closed,
                    TicketStatus::Resolved,
                ]))
                ->action(function (): void {
                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);

                    $ticketService->reopen(
                        ticket: $this->record,
                        performer: Filament::auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.ticket_reopened'))
                        ->success()
                        ->send();

                    $this->record->refresh();
                    $this->dispatch('$refresh');
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-help-desk::filament-help-desk.actions.view_ticket').': '.$this->record->reference_number;
    }

    protected function getForms(): array
    {
        return [
            'form',
            'commentForm',
        ];
    }
}
