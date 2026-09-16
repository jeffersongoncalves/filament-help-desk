<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ViewTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);

    $this->department = DepartmentFactory::new()->create();
});

function ticketFromApp(string $key, ?string $name, string $title): object
{
    $ticket = TicketFactory::new()->create([
        'department_id' => test()->department->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'title' => $title,
        'app_key' => $key,
    ]);

    // The creating hook stamps this application's own name. A ticket opened
    // elsewhere arrives carrying the label of the application it came from,
    // so overwrite it the way the satellite application would have written it.
    $ticket->update([
        'metadata' => $name === null ? null : ['app' => ['name' => $name]],
    ]);

    return $ticket->fresh();
}

it('shows the originating application column when tickets carry an app key', function () {
    ticketFromApp('app-a', 'Application A', 'Ticket from A');
    ticketFromApp('app-b', 'Application B', 'Ticket from B');

    livewire(ListTickets::class)
        ->assertSuccessful()
        ->assertSee('Application A')
        ->assertSee('Application B');
});

it('falls back to the app key when the ticket carries no application name', function () {
    ticketFromApp('app-without-name', null, 'Unnamed application ticket');

    livewire(ListTickets::class)
        ->assertSuccessful()
        ->assertSee('app-without-name');
});

it('filters the ticket list by originating application', function () {
    $fromA = ticketFromApp('app-a', 'Application A', 'Ticket from A');
    $fromB = ticketFromApp('app-b', 'Application B', 'Ticket from B');

    livewire(ListTickets::class)
        ->filterTable('app_key', 'app-a')
        ->assertCanSeeTableRecords([$fromA])
        ->assertCanNotSeeTableRecords([$fromB]);
});

it('shows the originating application on the ticket detail page', function () {
    $ticket = ticketFromApp('app-a', 'Application A', 'Ticket from A');

    livewire(ViewTicket::class, ['record' => $ticket->uuid])
        ->assertSuccessful()
        ->assertSee('Application A');
});

it('hides the column and the filter on a single-application install', function () {
    TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'title' => 'Local ticket',
        'app_key' => null,
    ]);

    $columns = collect(ListTickets::getResource()::getTicketTableColumns(showUser: true, showApplication: true))
        ->map(fn ($column): string => $column->getName());

    $filters = collect(ListTickets::getResource()::getTicketTableFilters(showApplication: true))
        ->map(fn ($filter): string => $filter->getName());

    expect($columns)->not->toContain('app_name')
        ->and($filters)->not->toContain('app_key');
});
