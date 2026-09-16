<?php

use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\ViewTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->operator = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('operator'));

    $this->actingAs($this->operator);

    $this->department = DepartmentFactory::new()->create();
});

it('shows the originating application column in the operator ticket list', function () {
    ticketFromApp($this->department->id, 'app-a', 'Application A', 'Ticket from A');
    ticketFromApp($this->department->id, 'app-b', 'Application B', 'Ticket from B');

    livewire(ListTickets::class)
        ->set('activeTab', 'all')
        ->assertSuccessful()
        ->assertSee('Application A')
        ->assertSee('Application B');
});

it('filters the operator ticket list by originating application', function () {
    $fromA = ticketFromApp($this->department->id, 'app-a', 'Application A', 'Ticket from A');
    $fromB = ticketFromApp($this->department->id, 'app-b', 'Application B', 'Ticket from B');

    livewire(ListTickets::class)
        ->set('activeTab', 'all')
        ->filterTable('app_key', 'app-a')
        ->assertCanSeeTableRecords([$fromA])
        ->assertCanNotSeeTableRecords([$fromB]);
});

it('shows the originating application on the operator ticket detail page', function () {
    $ticket = ticketFromApp($this->department->id, 'app-a', 'Application A', 'Ticket from A');

    livewire(ViewTicket::class, ['record' => $ticket->uuid])
        ->assertSuccessful()
        ->assertSee('Application A');
});
