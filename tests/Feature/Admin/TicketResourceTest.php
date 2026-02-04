<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);
});

it('can render the admin ticket list page', function () {
    livewire(ListTickets::class)
        ->assertSuccessful();
});
