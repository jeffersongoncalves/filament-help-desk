<?php

use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket;

use function Pest\Livewire\livewire;

it('does not show the originating application to the requester', function () {
    $user = UserFactory::new()->create();
    $department = DepartmentFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    $this->actingAs($user);

    // Every ticket in the User panel comes from the application it runs in,
    // so naming that application there is noise the requester cannot act on.
    $ticket = ticketFromApp($department->id, 'app-a', 'Application A', 'My ticket');
    $ticket->update(['user_id' => $user->id]);

    livewire(ViewTicket::class, ['record' => $ticket->uuid])
        ->assertSuccessful()
        ->assertDontSee('Application A');
});
