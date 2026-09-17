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
use JeffersonGoncalves\FilamentHelpDesk\Concerns\InteractsWithTicketComments;
use JeffersonGoncalves\FilamentHelpDesk\Driver;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Models\TicketAttachment;
use Symfony\Component\Mime\MimeTypes;

/**
 * @property Ticket|string $record
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

    /**
     * Livewire serialises an Eloquent model as a class and a key, and restores
     * it with a query. On a satellite that query hits a table the application
     * does not have, so between requests the record travels as its uuid — the
     * same thing the URL carries — and getTicket() resolves it back through
     * the repository on the way in.
     */
    public function dehydrate(): void
    {
        if (Driver::isApi()) {
            $this->record = $this->getTicket()->uuid;
        }
    }

    public function hydrate(): void
    {
        if (Driver::isApi()) {
            $this->record = $this->getTicket();
        }
    }

    public function getTicket(): Ticket
    {
        $record = $this->record;

        if ($record instanceof Ticket) {
            return $record;
        }

        try {
            return $this->record = HelpDesk::tickets()->findByUuid($record);
        } catch (TicketNotFoundException) {
            abort(404);
        }
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
                    ->maxSize(Driver::maxAttachmentSize())
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
            ->statePath('commentData');
    }

    public function getComments(): Collection
    {
        return $this->getCommentsForTimeline();
    }

    /**
     * The files uploaded with the ticket itself, as opposed to with a reply.
     *
     * @return Collection<int, TicketAttachment>
     */
    public function getTicketAttachments(): Collection
    {
        if (Driver::isApi()) {
            // Already on the ticket, from the show response. Calling the
            // relation would query a table the satellite does not have.
            return $this->getTicket()->getRelation('attachments')
                ->whereNull('comment_id')
                ->values();
        }

        return $this->getTicket()->attachments()->whereNull('comment_id')->get();
    }

    /**
     * Where a file is downloaded from.
     *
     * Never the disk URL: under the API driver the file sits on the central
     * application's disk, which this application has no credentials for, so a
     * URL here would be one that 404s for its own users. The route serves the
     * bytes through the repository on either transport.
     */
    public function getAttachmentUrl(TicketAttachment $attachment): string
    {
        $panelId = Filament::getCurrentOrDefaultPanel()->getId();

        return route("filament.{$panelId}.filament-help-desk.attachments.download", [
            'ticket' => $this->getTicket()->uuid,
            'attachment' => $attachment->uuid,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label(__('filament-help-desk::filament-help-desk.actions.close_ticket'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->getTicket()->status, [
                    TicketStatus::Open,
                    TicketStatus::InProgress,
                    TicketStatus::Pending,
                    TicketStatus::OnHold,
                ]))
                ->action(function (): void {
                    // Closing and reopening are the requester's own two status
                    // changes, and work on both transports since v1.8. The
                    // repository is what knows which one is in play — reaching
                    // for TicketService directly would hard-code the database.
                    HelpDesk::tickets()->close(
                        ticket: $this->getTicket(),
                        performer: Filament::auth()->user(),
                    );

                    // Re-resolved rather than kept: the status endpoint answers
                    // with the ticket alone, and the page still needs the
                    // comments and attachments the show response carries.
                    $this->record = HelpDesk::tickets()->findByUuid($this->getTicket()->uuid);

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
                ->visible(fn (): bool => in_array($this->getTicket()->status, [
                    TicketStatus::Closed,
                    TicketStatus::Resolved,
                ]))
                ->action(function (): void {
                    HelpDesk::tickets()->reopen(
                        ticket: $this->getTicket(),
                        performer: Filament::auth()->user(),
                    );

                    // Re-resolved rather than kept: the status endpoint answers
                    // with the ticket alone, and the page still needs the
                    // comments and attachments the show response carries.
                    $this->record = HelpDesk::tickets()->findByUuid($this->getTicket()->uuid);

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
        return __('filament-help-desk::filament-help-desk.actions.view_ticket').': '.$this->getTicket()->reference_number;
    }

    protected function getForms(): array
    {
        return [
            'form',
            'commentForm',
        ];
    }
}
