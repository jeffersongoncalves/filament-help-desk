<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\InteractsWithTicketComments;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Events\AttachmentAdded;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Models\TicketAttachment;
use JeffersonGoncalves\HelpDesk\Services\CommentService;
use JeffersonGoncalves\HelpDesk\Services\TicketService;

/**
 * @property-read Ticket $record
 * @property Schema $commentForm
 */
class ViewTicket extends ViewRecord
{
    use InteractsWithTicketComments;

    protected static string $resource = TicketResource::class;

    protected string $view = 'filament-help-desk::user.pages.view-ticket';

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

        $user = Filament::auth()->user();

        /** @var CommentService $commentService */
        $commentService = app(CommentService::class);

        $comment = $commentService->addReply(
            ticket: $this->record,
            author: $user,
            body: $data['body'],
        );

        $attachments = $data['attachments'] ?? [];

        if (! empty($attachments)) {
            $disk = config('help-desk.ticket.attachment_disk', 'local');
            $storage = Storage::disk($disk);
            $storagePath = config('help-desk.ticket.attachment_path', 'help-desk/attachments');

            foreach ($attachments as $filePath) {
                $mimeType = $storage->mimeType($filePath) ?: 'application/octet-stream';
                $fileSize = $storage->size($filePath) ?: 0;
                $destination = $storagePath.'/'.$this->record->uuid.'/'.basename($filePath);

                $storage->move($filePath, $destination);

                $attachment = TicketAttachment::create([
                    'ticket_id' => $this->record->id,
                    'comment_id' => $comment->id,
                    'uploaded_by_type' => $user->getMorphClass(),
                    'uploaded_by_id' => $user->getKey(),
                    'file_name' => basename($filePath),
                    'file_path' => $destination,
                    'disk' => $disk,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                ]);

                event(new AttachmentAdded($this->record, $attachment));
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label(__('filament-help-desk::filament-help-desk.actions.close_ticket'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, [
                    TicketStatus::Open,
                    TicketStatus::InProgress,
                    TicketStatus::Pending,
                    TicketStatus::OnHold,
                ]))
                ->action(function (): void {
                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);

                    $ticketService->close(
                        ticket: $this->record,
                        performer: Filament::auth()->user(),
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.ticket_closed'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
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

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.notifications.ticket_reopened'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
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
