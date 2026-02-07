<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Models\TicketComment;
use JeffersonGoncalves\HelpDesk\Services\AttachmentService;
use JeffersonGoncalves\HelpDesk\Services\CommentService;

/**
 * Provides comment form schema, submission logic, and timeline retrieval for ticket views.
 *
 * This trait is intended for use on Filament page classes (e.g. ViewTicket)
 * that display a ticket record and allow adding comments/replies.
 *
 * @property Ticket $record The ticket record associated with this page.
 */
trait InteractsWithTicketComments
{
    /**
     * Get the form schema for the comment/reply form.
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public function getCommentFormSchema(): array
    {
        return [
            RichEditor::make('body')
                ->label(__('filament-help-desk::filament-help-desk.fields.reply'))
                ->required()
                ->columnSpanFull(),

            Toggle::make('is_internal')
                ->label(__('filament-help-desk::filament-help-desk.fields.internal_note'))
                ->helperText(__('filament-help-desk::filament-help-desk.comments.internal_note_help'))
                ->default(false),

            FileUpload::make('attachments')
                ->label(__('filament-help-desk::filament-help-desk.fields.attachments'))
                ->multiple()
                ->disk(config('help-desk.ticket.attachment_disk', 'local'))
                ->directory(config('help-desk.ticket.attachment_path', 'help-desk/attachments'))
                ->acceptedFileTypes(
                    collect(config('help-desk.ticket.allowed_extensions', []))
                        ->flatMap(fn (string $ext): array => \Symfony\Component\Mime\MimeTypes::getDefault()->getMimeTypes($ext))
                        ->unique()
                        ->values()
                        ->toArray()
                )
                ->maxSize(config('help-desk.ticket.max_file_size', 10240))
                ->maxFiles(config('help-desk.ticket.max_attachments_per_comment', 5))
                ->columnSpanFull(),
        ];
    }

    /**
     * Submit a new comment on the current ticket.
     *
     * Uses the CommentService to add either a reply or an internal note
     * based on the is_internal toggle state. After submission, the form
     * is reset and the page is refreshed.
     */
    public function submitComment(): void
    {
        $data = $this->commentForm->getState();

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

    /**
     * Get all comments for the ticket timeline, ordered by most recent first.
     *
     * @return Collection<int, TicketComment>
     */
    public function getCommentsForTimeline(): Collection
    {
        return $this->record
            ->comments()
            ->with(['author', 'attachments'])
            ->latest()
            ->get();
    }
}
