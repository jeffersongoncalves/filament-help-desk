<?php

use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->operator = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('operator'));

    $this->actingAs($this->operator);
});

it('can render the operator ticket list page', function () {
    livewire(ListTickets::class)
        ->assertSuccessful();
});
