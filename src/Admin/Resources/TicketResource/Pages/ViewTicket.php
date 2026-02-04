<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\InteractsWithTicketComments;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Services\TicketService;

/**
 * @property-read Ticket $record
 * @property Form $commentForm
 */
class ViewTicket extends ViewRecord
{
    use InteractsWithTicketComments;

    protected static string $resource = TicketResource::class;

    protected static string $view = 'filament-help-desk::admin.pages.view-ticket';

    public ?array $commentData = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->commentForm->fill();
    }

    public function commentForm(Form $form): Form
    {
        return $form
            ->schema([
                RichEditor::make('body')
                    ->label(__('filament-help-desk::filament-help-desk.fields.reply'))
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
                    ->helperText(__('filament-help-desk::filament-help-desk.helpers.internal_note'))
                    ->default(false),

                FileUpload::make('attachments')
                    ->label(__('filament-help-desk::filament-help-desk.fields.attachments'))
                    ->multiple()
                    ->maxFiles(config('help-desk.ticket.max_attachments_per_comment', 5))
                    ->maxSize(config('help-desk.ticket.max_file_size', 10240))
                    ->acceptedFileTypes(
                        collect(config('help-desk.ticket.allowed_extensions', []))
                            ->map(fn (string $ext): string => '.' . $ext)
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
                ->label(__('filament-help-desk::filament-help-desk.resource.ticket.actions.assign'))
                ->icon('heroicon-o-user-plus')
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

                    $this->record->update([
                        'assigned_to_type' => $operatorModel,
                        'assigned_to_id' => $data['assigned_to_id'],
                    ]);

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.resource.ticket.notifications.ticket_assigned'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['assigned_to_id', 'assigned_to_type']);
                }),

            Actions\Action::make('change_status')
                ->label(__('filament-help-desk::filament-help-desk.resource.ticket.actions.change_status'))
                ->icon('heroicon-o-arrow-path')
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
                    $this->record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.resource.ticket.notifications.status_changed'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('change_priority')
                ->label(__('filament-help-desk::filament-help-desk.resource.ticket.actions.change_priority'))
                ->icon('heroicon-o-flag')
                ->color('gray')
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
                    $this->record->update(['priority' => $data['priority']]);

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.resource.ticket.notifications.priority_changed'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['priority']);
                }),

            Actions\Action::make('close')
                ->label(__('filament-help-desk::filament-help-desk.resource.ticket.actions.close'))
                ->icon('heroicon-o-x-circle')
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
                        user: auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.resource.ticket.notifications.ticket_closed'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'closed_at']);
                }),

            Actions\Action::make('reopen')
                ->label(__('filament-help-desk::filament-help-desk.resource.ticket.actions.reopen'))
                ->icon('heroicon-o-arrow-path')
                ->color('success')
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
                        user: auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.resource.ticket.notifications.ticket_reopened'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'closed_at']);
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.ticket.pages.view.title', [
            'reference' => $this->record->reference_number,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'form',
            'commentForm',
        ];
    }
}
