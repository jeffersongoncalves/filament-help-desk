<?php

use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->operator = UserFactory::new()->create();

    $panel = Panel::make()
        ->default()
        ->id('operator')
        ->path('operator')
        ->login()
        ->plugin(FilamentHelpDeskOperatorPlugin::make());

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    $this->actingAs($this->operator);
});

it('can render the operator ticket list page', function () {
    livewire(ListTickets::class)
        ->assertSuccessful();
});
