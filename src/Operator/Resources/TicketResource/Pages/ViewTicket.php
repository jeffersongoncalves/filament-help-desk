<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\StateCasts\BooleanStateCast;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\InteractsWithTicketComments;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Events\AttachmentAdded;
use JeffersonGoncalves\HelpDesk\Models\CannedResponse;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Models\TicketAttachment;
use JeffersonGoncalves\HelpDesk\Services\CommentService;
use JeffersonGoncalves\HelpDesk\Services\TicketService;
use Symfony\Component\Mime\MimeTypes;

/**
 * @property-read Ticket $record
 * @property Schema $commentForm
 */
class ViewTicket extends ViewRecord
{
    use InteractsWithTicketComments;

    /**
     * The record is always a model on this panel: the Admin and Operator
     * panels refuse to register on the API driver, so there is nothing to
     * resolve back.
     */
    public function getTicket(): Ticket
    {
        return $this->record;
    }

    protected static string $resource = TicketResource::class;

    protected string $view = 'filament-help-desk::operator.pages.view-ticket';

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
                ToggleButtons::make('is_internal')
                    ->label(__('filament-help-desk::filament-help-desk.comments.reply_mode'))
                    ->helperText(__('filament-help-desk::filament-help-desk.comments.internal_note_help'))
                    ->options([
                        0 => __('filament-help-desk::filament-help-desk.comments.mode_public'),
                        1 => __('filament-help-desk::filament-help-desk.comments.mode_internal'),
                    ])
                    ->icons([
                        0 => Heroicon::OutlinedChatBubbleLeftRight,
                        1 => Heroicon::OutlinedLockClosed,
                    ])
                    ->colors([
                        0 => 'primary',
                        1 => 'warning',
                    ])
                    ->stateCast(app(BooleanStateCast::class, ['isStoredAsInt' => true]))
                    ->default(false)
                    ->inline()
                    ->live()
                    ->required(),

                Group::make([
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

                    FileUpload::make('attachments')
                        ->label(__('filament-help-desk::filament-help-desk.fields.attachments'))
                        ->multiple()
                        ->maxFiles(config('help-desk.ticket.max_attachments_per_comment', 5))
                        ->maxSize(config('help-desk.ticket.max_file_size', 10240))
                        ->acceptedFileTypes(
                            collect(config('help-desk.ticket.allowed_extensions', []))
                                ->flatMap(fn (string $ext): array => MimeTypes::getDefault()->getMimeTypes($ext))
                                ->unique()
                                ->values()
                                ->toArray()
                        )
                        ->disk(config('help-desk.ticket.attachment_disk', 'local'))
                        ->directory(config('help-desk.ticket.attachment_path', 'help-desk/attachments'))
                        ->columnSpanFull(),
                ])
                    ->extraAttributes(
                        fn (Get $get): array => ['class' => $get('is_internal') ? 'fi-hd-comment-form-internal' : 'fi-hd-comment-form-public'],
                    ),
            ])
            ->statePath('commentData');
    }

    public function isInternalNote(): bool
    {
        return (bool) ($this->commentData['is_internal'] ?? false);
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
            $disk = config('help-desk.ticket.attachment_disk', 'local');
            $storage = Storage::disk($disk);
            $storagePath = config('help-desk.ticket.attachment_path', 'help-desk/attachments');

            foreach ($attachments as $filePath) {
                $mimeType = $storage->mimeType($filePath) ?: 'application/octet-stream';
                $fileSize = $storage->size($filePath) ?: 0;
                $destination = $storagePath.'/'.$ticket->uuid.'/'.basename($filePath);

                $storage->move($filePath, $destination);

                $attachment = TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'uploaded_by_type' => $author->getMorphClass(),
                    'uploaded_by_id' => $author->getKey(),
                    'file_name' => basename($filePath),
                    'file_path' => $destination,
                    'disk' => $disk,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'metadata' => ['uploader' => TicketAttachment::snapshotOf($author)],
                ]);

                event(new AttachmentAdded($ticket, $attachment));
            }
        }

        $this->commentForm->fill();

        Notification::make()
            ->title(__('filament-help-desk::filament-help-desk.notifications.comment_added'))
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function getComments(): Collection
    {
        return $this->getCommentsForTimeline();
    }

    /**
     * How many other open tickets the same requester has, shown in the
     * sidebar so the operator sees the wider context without leaving
     * this ticket.
     */
    public function getOtherOpenTicketsCount(): int
    {
        return Ticket::query()
            ->where('user_type', $this->record->user_type)
            ->where('user_id', $this->record->user_id)
            ->where('id', '!=', $this->record->id)
            ->open()
            ->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_to_me')
                ->label(__('filament-help-desk::filament-help-desk.actions.assign_to_me'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->assigned_to_id !== Filament::auth()->id()
                    || $this->record->assigned_to_type !== Filament::auth()->user()->getMorphClass())
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
                ->icon(Heroicon::OutlinedArrowPath)
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

            Actions\Action::make('use_canned_response')
                ->label(__('filament-help-desk::filament-help-desk.actions.use_canned_response'))
                ->icon(Heroicon::OutlinedDocumentText)
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
                ->icon(Heroicon::OutlinedXCircle)
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
