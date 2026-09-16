<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ViewTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Models\TicketComment;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);

    // A ticket opened by an application whose user model this one does not
    // have: the morph type resolves to nothing, only the snapshot remains.
    $this->ticket = TicketFactory::new()->create([
        'department_id' => DepartmentFactory::new()->create()->id,
        'user_type' => 'satellite-app-user',
        'user_id' => 9999,
        'title' => 'Ticket from another application',
        'metadata' => [
            'requester' => [
                'name' => 'Ada Lovelace',
                'email' => 'ada@satellite.test',
            ],
        ],
    ]);
});

it('lists a ticket whose requester model is not installed here', function () {
    livewire(ListTickets::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->ticket])
        ->assertSee('Ada Lovelace');
});

it('shows the requester snapshot on the ticket detail page', function () {
    livewire(ViewTicket::class, ['record' => $this->ticket->uuid])
        ->assertSuccessful()
        ->assertSee('Ada Lovelace');
});

it('shows the comment author snapshot when the author model is not installed here', function () {
    TicketComment::create([
        'ticket_id' => $this->ticket->id,
        'author_type' => 'satellite-app-user',
        'author_id' => 9999,
        'body' => 'Comment written in the satellite application.',
        'is_internal' => false,
        'metadata' => [
            'author' => [
                'name' => 'Grace Hopper',
                'email' => 'grace@satellite.test',
            ],
        ],
    ]);

    livewire(ViewTicket::class, ['record' => $this->ticket->uuid])
        ->assertSuccessful()
        ->assertSee('Grace Hopper');
});
