<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Services\AttachmentService;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

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

        if (empty($attachments)) {
            return;
        }

        /** @var AttachmentService $attachmentService */
        $attachmentService = app(AttachmentService::class);
        $disk = config('help-desk.ticket.attachment_disk', 'local');
        $user = Filament::auth()->user();

        foreach ($attachments as $filePath) {
            $storage = Storage::disk($disk);
            $attachmentService->storeFromPath(
                ticket: $ticket,
                filePath: $filePath,
                fileName: basename($filePath),
                mimeType: $storage->mimeType($filePath) ?: 'application/octet-stream',
                fileSize: $storage->size($filePath) ?: 0,
                uploadedBy: $user,
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return __('filament-help-desk::filament-help-desk.actions.create_ticket');
    }
}
