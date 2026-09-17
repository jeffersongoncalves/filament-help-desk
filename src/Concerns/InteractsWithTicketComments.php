<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Driver;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Models\TicketComment;
use Symfony\Component\Mime\MimeTypes;

/**
 * Provides comment form schema, submission logic, and timeline retrieval for ticket views.
 *
 * This trait is intended for use on Filament page classes (e.g. ViewTicket)
 * that display a ticket record and allow adding comments/replies.
 */
trait InteractsWithTicketComments
{
    use StoresTicketAttachments;

    /**
     * The ticket this page is showing.
     *
     * A method rather than $this->record, because on a satellite the record is
     * not always a model: Livewire would restore one with a query against a
     * table the application does not have, so the User page hands it on as a
     * uuid between requests and resolves it back here.
     */
    abstract public function getTicket(): Ticket;

    /**
     * Get the form schema for the comment/reply form.
     *
     * @return array<int, Component>
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
                // An internal note belongs to the operator side and the API
                // refuses it. A toggle that throws when switched on is worse
                // than no toggle.
                ->visible(fn (): bool => ! Driver::isApi())
                ->default(false),

            FileUpload::make('attachments')
                ->label(__('filament-help-desk::filament-help-desk.fields.attachments'))
                ->multiple()
                ->disk(config('help-desk.ticket.attachment_disk', 'local'))
                ->directory(config('help-desk.ticket.attachment_path', 'help-desk/attachments'))
                ->acceptedFileTypes(
                    collect(config('help-desk.ticket.allowed_extensions', []))
                        ->flatMap(fn (string $ext): array => MimeTypes::getDefault()->getMimeTypes($ext))
                        ->unique()
                        ->values()
                        ->toArray()
                )
                ->maxSize(Driver::maxAttachmentSize())
                ->maxFiles(config('help-desk.ticket.max_attachments_per_comment', 5))
                ->columnSpanFull(),
        ];
    }

    /**
     * Submit a new comment on the current ticket.
     *
     * Routed through the comment repository rather than the database service,
     * so the same page works on a satellite: what changes between transports
     * is which implementation the contract resolves to, not what this does.
     */
    public function submitComment(): void
    {
        $data = $this->commentForm->getState();

        if (blank($data['body'] ?? null)) {
            return;
        }

        /** @var Ticket $ticket */
        $ticket = $this->getTicket();

        $author = Filament::auth()->user();

        $comment = ($data['is_internal'] ?? false)
            ? HelpDesk::comments()->addNote(ticket: $ticket, author: $author, body: $data['body'])
            : HelpDesk::comments()->addReply(ticket: $ticket, author: $author, body: $data['body']);

        $this->storeTicketAttachments($ticket, $data['attachments'] ?? [], $author, $comment);

        if (Driver::isApi()) {
            // The timeline reads relations the show response carried, so the
            // reply just posted is not in them. A database driver re-queries
            // on render and needs nothing here.
            $this->record = HelpDesk::tickets()->findByUuid($ticket->uuid);
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
        if (Driver::isApi()) {
            // The show response already carried them, along with the
            // attachments hung off each one. Re-reading the relation would
            // query a table the satellite does not have.
            return $this->getTicket()->getRelation('comments')
                ->sortByDesc('created_at')
                ->values();
        }

        return $this->getTicket()
            ->comments()
            // The author is left out on purpose: eager loading a morphTo
            // instantiates every stored type, which is fatal for a class
            // another application owns. TicketComment::$author_name loads
            // it only when it resolves here.
            ->with('attachments')
            ->latest()
            ->get();
    }
}
