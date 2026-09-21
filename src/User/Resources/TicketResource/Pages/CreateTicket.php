<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\StoresTicketAttachments;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class CreateTicket extends CreateRecord
{
    use StoresTicketAttachments;

    protected static string $resource = TicketResource::class;

    /**
     * Create through the repository rather than the model.
     *
     * It stamps the requester, the identity snapshot and the source on either
     * transport — which is also why `mutateFormDataBeforeCreate()` is gone:
     * setting `user_type`, `user_id` and `source` here was doing by hand what
     * the repository does, and a satellite has no model to save anyway.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        // The uploads are handled afterwards, once there is a ticket to hang
        // them off. Left in, they would travel to the central application as
        // an unknown field.
        unset($data['attachments']);

        $user = Filament::auth()->user();

        // Carried over from the requester so the company-scoped list/view in
        // TicketResource::getEloquentQuery() has something to match against.
        // The package itself has no notion of "company" and never sets this.
        if (($companyId = $user->company_id ?? null) !== null) {
            $data['company_id'] = $companyId;
        }

        return HelpDesk::createTicket($data, $user);
    }

    protected function afterCreate(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->record;

        $this->storeTicketAttachments(
            $ticket,
            $this->data['attachments'] ?? [],
            Filament::auth()->user(),
        );
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
