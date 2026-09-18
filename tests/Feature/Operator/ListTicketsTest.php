<?php

use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;

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

it('shows the claim action only on unassigned tickets', function () {
    $department = DepartmentFactory::new()->create();
    $requester = UserFactory::new()->create();
    $otherOperator = UserFactory::new()->create();

    $unassigned = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
        'assigned_to_type' => null,
        'assigned_to_id' => null,
    ]);

    $assigned = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
        'assigned_to_type' => User::class,
        'assigned_to_id' => $otherOperator->id,
    ]);

    livewire(ListTickets::class)
        ->set('activeTab', 'all')
        ->assertTableActionVisible('claim', $unassigned)
        ->assertTableActionHidden('claim', $assigned);
});

it('claims a ticket for the current operator with one click', function () {
    $department = DepartmentFactory::new()->create();
    $requester = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
        'assigned_to_type' => null,
        'assigned_to_id' => null,
    ]);

    livewire(ListTickets::class)
        ->set('activeTab', 'unassigned')
        ->callTableAction('claim', $ticket)
        ->assertNotified();

    expect($ticket->refresh())
        ->assigned_to_id->toBe($this->operator->id)
        ->assigned_to_type->toBe(User::class);
});

it('computes the three tab badge counts in a single query', function () {
    $department = DepartmentFactory::new()->create();
    $requester = UserFactory::new()->create();

    TicketFactory::new()->count(3)->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
        'assigned_to_type' => null,
        'assigned_to_id' => null,
    ]);

    DB::enableQueryLog();

    $tabs = (new ListTickets)->getTabs();

    $ticketCountQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'help_desk_tickets')
            && (str_contains($query['query'], 'count(') || str_contains($query['query'], 'sum(')));

    DB::disableQueryLog();

    expect($ticketCountQueries)->toHaveCount(1)
        ->and($tabs['unassigned']->getBadge())->toBe('3');
});
