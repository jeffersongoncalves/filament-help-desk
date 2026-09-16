<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\CreateTicket;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Relation::enforceMorphMap(['app-user' => User::class]);

    $this->user = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    $this->actingAs($this->user);
});

afterEach(function () {
    Relation::morphMap([], false);
    Relation::requireMorphMap(false);
});

it('stores the requester using the morph alias', function () {
    $department = DepartmentFactory::new()->create();

    livewire(CreateTicket::class)
        ->fillForm([
            'title' => 'Morph Mapped Ticket',
            'description' => 'Ticket created while a morph map is enforced.',
            'department_id' => $department->id,
            'priority' => 'medium',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->where('title', 'Morph Mapped Ticket')->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->user_type)->toBe('app-user')
        ->and($ticket->user_id)->toBe($this->user->id);
});

it('scopes the ticket list by the morph alias and ignores rows keyed by the class name', function () {
    $department = DepartmentFactory::new()->create();

    $myTicket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => 'app-user',
        'user_id' => $this->user->id,
        'title' => 'My mapped ticket',
    ]);

    // Same user id, but written by another app that did not share this morph alias.
    $foreignTicket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'title' => 'Foreign app ticket',
    ]);

    livewire(ListTickets::class)
        ->assertCanSeeTableRecords([$myTicket])
        ->assertCanNotSeeTableRecords([$foreignTicket]);
});
