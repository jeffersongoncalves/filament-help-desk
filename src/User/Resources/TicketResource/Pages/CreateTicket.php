<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Services\AttachmentService;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['user_type'] = get_class($user);
        $data['user_id'] = $user->getAuthIdentifier();
        $data['source'] = 'web';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->record;
        $attachments = $this->data['attachments'] ?? [];

        if (! empty($attachments)) {
            /** @var AttachmentService $attachmentService */
            $attachmentService = app(AttachmentService::class);

            foreach ($attachments as $attachment) {
                $attachmentService->store(
                    ticket: $ticket,
                    file: $attachment,
                    author: auth()->user(),
                );
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.ticket.pages.create.title');
    }
}
