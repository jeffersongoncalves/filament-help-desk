<?php

use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\EditTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;

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

it('can render the operator edit ticket page', function () {
    $department = DepartmentFactory::new()->create();
    $user = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $user->id,
    ]);

    livewire(EditTicket::class, [
        'record' => $ticket->uuid,
    ])
        ->assertSuccessful();
});
