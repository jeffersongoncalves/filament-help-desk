<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Events\AttachmentAdded;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Models\TicketAttachment;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        $data['user_type'] = get_class($user);
        $data['user_id'] = $user->getAuthIdentifier();
        $data['source'] = 'web';

        if (! empty($data['assigned_to_id'])) {
            $operatorModel = config('help-desk.models.operator');
            $data['assigned_to_type'] = $operatorModel;
        }

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

        $disk = config('help-desk.ticket.attachment_disk', 'local');
        $storage = Storage::disk($disk);
        $storagePath = config('help-desk.ticket.attachment_path', 'help-desk/attachments');
        $user = Filament::auth()->user();

        foreach ($attachments as $filePath) {
            $mimeType = $storage->mimeType($filePath) ?: 'application/octet-stream';
            $fileSize = $storage->size($filePath) ?: 0;
            $destination = $storagePath.'/'.$ticket->uuid.'/'.basename($filePath);

            $storage->move($filePath, $destination);

            $attachment = TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'uploaded_by_type' => $user->getMorphClass(),
                'uploaded_by_id' => $user->getKey(),
                'file_name' => basename($filePath),
                'file_path' => $destination,
                'disk' => $disk,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);

            event(new AttachmentAdded($ticket, $attachment));
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
