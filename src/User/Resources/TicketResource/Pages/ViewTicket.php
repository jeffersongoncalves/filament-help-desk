<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\InteractsWithTicketComments;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
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

    protected static string $view = 'filament-help-desk::user.pages.view-ticket';

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
                    ->label(__('filament-help-desk::filament-help-desk.resource.ticket.fields.comment_body'))
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
                    ->label(__('filament-help-desk::filament-help-desk.resource.ticket.fields.attachments'))
                    ->multiple()
                    ->maxFiles(config('help-desk.ticket.max_attachments_per_comment', 5))
                    ->maxSize(config('help-desk.ticket.max_file_size', 10240))
                    ->acceptedFileTypes(config('help-desk.ticket.allowed_extensions', []))
                    ->disk(config('help-desk.ticket.attachment_disk', 'public'))
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

        $comment = $commentService->addReply(
            ticket: $this->record,
            author: auth()->user(),
            body: $data['body'],
        );

        $attachments = $data['attachments'] ?? [];

        if (! empty($attachments)) {
            /** @var AttachmentService $attachmentService */
            $attachmentService = app(AttachmentService::class);

            foreach ($attachments as $attachment) {
                $attachmentService->store(
                    ticket: $this->record,
                    file: $attachment,
                    author: auth()->user(),
                    comment: $comment,
                );
            }
        }

        $this->commentForm->fill();

        Notification::make()
            ->title(__('filament-help-desk::filament-help-desk.resource.ticket.notifications.comment_added'))
            ->success()
            ->send();
    }

    public function getComments(): array
    {
        return $this->getCommentsForTimeline();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label(__('filament-help-desk::filament-help-desk.resource.ticket.actions.close'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('filament-help-desk::filament-help-desk.resource.ticket.actions.close_confirm_heading'))
                ->modalDescription(__('filament-help-desk::filament-help-desk.resource.ticket.actions.close_confirm_description'))
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
                        user: auth()->user(),
                    );

                    Notification::make()
                        ->title(__('filament-help-desk::filament-help-desk.resource.ticket.notifications.ticket_closed'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('reopen')
                ->label(__('filament-help-desk::filament-help-desk.resource.ticket.actions.reopen'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('filament-help-desk::filament-help-desk.resource.ticket.actions.reopen_confirm_heading'))
                ->modalDescription(__('filament-help-desk::filament-help-desk.resource.ticket.actions.reopen_confirm_description'))
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

                    $this->refreshFormData(['status']);
                }),
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
