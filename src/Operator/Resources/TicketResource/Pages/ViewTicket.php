<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\InteractsWithTicketComments;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\CannedResponse;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Services\AttachmentService;
use JeffersonGoncalves\HelpDesk\Services\CommentService;
use JeffersonGoncalves\HelpDesk\Services\TicketService;

/**
 * @property-read Ticket $record
 * @property Form $commentForm
 */
class ViewTicket extends ViewRecord
{
    use InteractsWithTicketComments;

    protected static string $resource = TicketResource::class;

    protected static string $view = 'filament-help-desk::operator.pages.view-ticket';

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
                    ->label(__('filament-help-desk::filament-help-desk.comments.internal_note'))
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

    public function submitComment(): void
    {
        $data = $this->commentForm->getState();

        if (empty($data['body'])) {
            return;
        }

        /** @var CommentService $commentService */
        $commentService = app(CommentService::class);

        /** @var Ticket $ticket */
        $ticket = $this->record;

        $author = Filament::auth()->user();

        if ($data['is_internal'] ?? false) {
            $comment = $commentService->addNote(
                ticket: $ticket,
                author: $author,
                body: $data['body'],
            );
        } else {
            $comment = $commentService->addReply(
                ticket: $ticket,
                author: $author,
                body: $data['body'],
            );
        }

        $attachments = $data['attachments'] ?? [];

        if (! empty($attachments)) {
            /** @var AttachmentService $attachmentService */
            $attachmentService = app(AttachmentService::class);
            $disk = config('help-desk.ticket.attachment_disk', 'local');

            foreach ($attachments as $filePath) {
                $storage = Storage::disk($disk);
                $attachmentService->storeFromPath(
                    ticket: $ticket,
                    filePath: $filePath,
                    fileName: basename($filePath),
                    mimeType: $storage->mimeType($filePath) ?: 'application/octet-stream',
                    fileSize: $storage->size($filePath) ?: 0,
                    uploadedBy: $author,
                    comment: $comment,
                );
            }
        }

        $this->commentForm->fill();

        Notification::make()
            ->title(__('filament-help-desk::filament-help-desk.notifications.comment_added'))
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function getComments(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getCommentsForTimeline();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_to_me')
                ->label(__('filament-help-desk::filament-help-desk.actions.assign_to_me'))
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->assigned_to_id !== Filament::auth()->id()
                    || $this->record->assigned_to_type !== get_class(Filament::auth()->user()))
                ->action(function (): void {
                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);

                    $operator = Filament::auth()->user();

                    $ticketService->assign(
                        ticket: $this->record,
                        operator: $operator,
                        assignedBy: $operator,
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
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status !== TicketStatus::Closed)
                ->form([
                    Select::make('status')
                        ->label(__('filament-help-desk::filament-help-desk.fields.status'))
                        ->options(function (): array {
                            $allowedTransitions = $this->record->status->allowedTransitions();

                            return collect($allowedTransitions)
                                ->mapWithKeys(fn (TicketStatus $status): array => [
                                    $status->value => $status->label(),
                                ])
                                ->toArray();
                        })
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
                ->icon('heroicon-o-flag')
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

            Actions\Action::make('use_canned_response')
                ->label(__('filament-help-desk::filament-help-desk.actions.use_canned_response'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn (): bool => ! in_array($this->record->status, [TicketStatus::Closed, TicketStatus::Resolved]))
                ->form([
                    Select::make('canned_response_id')
                        ->label(__('filament-help-desk::filament-help-desk.placeholders.select_canned_response'))
                        ->options(function (): array {
                            return CannedResponse::query()
                                ->active()
                                ->ordered()
                                ->forDepartment($this->record->department_id)
                                ->pluck('title', 'id')
                                ->toArray();
                        })
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->action(function (array $data): void {
                    $cannedResponse = CannedResponse::find($data['canned_response_id']);

                    if (! $cannedResponse) {
                        return;
                    }

                    /** @var CommentService $commentService */
                    $commentService = app(CommentService::class);

                    $commentService->addReply(
                        ticket: $this->record,
                        author: Filament::auth()->user(),
                        body: $cannedResponse->body,
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.comment_added'))
                        ->success()
                        ->send();

                    $this->dispatch('$refresh');
                }),

            Actions\Action::make('close')
                ->label(__('filament-help-desk::filament-help-desk.actions.close_ticket'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! in_array($this->record->status, [TicketStatus::Closed, TicketStatus::Resolved]))
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
                ->icon('heroicon-o-arrow-path')
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

            Actions\EditAction::make(),
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
