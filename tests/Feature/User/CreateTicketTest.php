<?php

use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskUserPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\CreateTicket;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = UserFactory::new()->create();

    $panel = Panel::make()
        ->default()
        ->id('user')
        ->path('user')
        ->login()
        ->plugin(FilamentHelpDeskUserPlugin::make());

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    $this->actingAs($this->user);
});

it('can render the create ticket page', function () {
    livewire(CreateTicket::class)
        ->assertSuccessful();
});

it('can create a ticket', function () {
    $department = DepartmentFactory::new()->create();

    livewire(CreateTicket::class)
        ->fillForm([
            'title' => 'Test Ticket Title',
            'description' => 'Test ticket description content.',
            'department_id' => $department->id,
            'priority' => 'medium',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->where('title', 'Test Ticket Title')->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->user_type)->toBe(User::class)
        ->and($ticket->user_id)->toBe($this->user->id)
        ->and($ticket->department_id)->toBe($department->id)
        ->and($ticket->source)->toBe('web');
});
