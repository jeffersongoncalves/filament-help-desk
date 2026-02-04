<?php

use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    $panel = Panel::make()
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->plugin(FilamentHelpDeskAdminPlugin::make());

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    $this->actingAs($this->admin);
});

it('can render the admin ticket list page', function () {
    livewire(ListTickets::class)
        ->assertSuccessful();
});
