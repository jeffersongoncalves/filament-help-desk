<?php

use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ListTickets;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    $this->actingAs($this->user);
});

it('can render the user ticket list page', function () {
    livewire(ListTickets::class)
        ->assertSuccessful();
});

it('only shows tickets belonging to the authenticated user', function () {
    $department = DepartmentFactory::new()->create();

    // Create a ticket for the authenticated user
    $myTicket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'title' => 'My ticket title',
    ]);

    // Create a ticket for a different user
    $otherUser = UserFactory::new()->create();
    $otherTicket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $otherUser->id,
        'title' => 'Other user ticket',
    ]);

    livewire(ListTickets::class)
        ->assertCanSeeTableRecords([$myTicket])
        ->assertCanNotSeeTableRecords([$otherTicket]);
});
