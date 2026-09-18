<?php

use Filament\Panel;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Pages\TicketsKanbanBoard;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Events\TicketStatusChanged;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->operator = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('operator'));

    $this->actingAs($this->operator);
});

it('does not register the kanban page by default', function () {
    $panel = Panel::make()->id('kanban-test-default');

    FilamentHelpDeskOperatorPlugin::make()->register($panel);

    expect($panel->getPages())->not->toContain(TicketsKanbanBoard::class);
});

it('registers the kanban page once enabled, since the package is installed', function () {
    $panel = Panel::make()->id('kanban-test-enabled');

    FilamentHelpDeskOperatorPlugin::make()->kanban()->register($panel);

    expect($panel->getPages())->toContain(TicketsKanbanBoard::class);
});

it('moves a ticket to an allowed status via TicketService, not a raw update', function () {
    Event::fake([TicketStatusChanged::class]);

    $department = DepartmentFactory::new()->create();
    $requester = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
        'status' => TicketStatus::Open,
    ]);

    livewire(TicketsKanbanBoard::class)
        ->call('onStatusChanged', $ticket->id, TicketStatus::InProgress->value, [])
        ->assertNotDispatched('kanban-move-rejected');

    expect($ticket->refresh()->status)->toBe(TicketStatus::InProgress);

    // Only TicketService::changeStatus() fires this — a bare $record->update()
    // (what the base KanbanBoard does) would have moved the status silently,
    // skipping SLA-pause tracking and the history entry that listen on it.
    Event::assertDispatched(TicketStatusChanged::class, fn (TicketStatusChanged $event): bool => $event->ticket->is($ticket)
        && $event->oldStatus === TicketStatus::Open
        && $event->newStatus === TicketStatus::InProgress);
});

it('rejects a drag to a status the ticket cannot transition to', function () {
    $department = DepartmentFactory::new()->create();
    $requester = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
        'status' => TicketStatus::Resolved,
    ]);

    livewire(TicketsKanbanBoard::class)
        ->call('onStatusChanged', $ticket->id, TicketStatus::InProgress->value, [])
        ->assertDispatched('kanban-move-rejected');

    expect($ticket->refresh()->status)->toBe(TicketStatus::Resolved);
});

it('does not persist order on a pure in-column reorder, there is no order_column', function () {
    livewire(TicketsKanbanBoard::class)
        ->call('onSortChanged', [3, 1, 2])
        ->assertSuccessful();
});
