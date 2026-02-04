<?php

use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    $this->actingAs($this->user);
});

it('can render the view ticket page', function () {
    $department = DepartmentFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    livewire(ViewTicket::class, [
        'record' => $ticket->uuid,
    ])
        ->assertSuccessful();
});

it('can display ticket details', function () {
    $department = DepartmentFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'title' => 'Specific Ticket Title',
    ]);

    livewire(ViewTicket::class, [
        'record' => $ticket->uuid,
    ])
        ->assertSuccessful()
        ->assertSee($ticket->reference_number);
});
