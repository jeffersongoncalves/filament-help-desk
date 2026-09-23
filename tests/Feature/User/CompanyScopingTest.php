<?php

use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\CreateTicket;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->department = DepartmentFactory::new()->create();
});

it('shows every ticket raised by the same company, not just the requester\'s own', function () {
    $me = UserFactory::new()->create(['company_id' => 'acme']);
    $colleague = UserFactory::new()->create(['company_id' => 'acme']);
    $outsider = UserFactory::new()->create(['company_id' => 'globex']);

    $mine = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $me->id,
        'company_id' => 'acme',
    ]);

    $colleagues = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $colleague->id,
        'company_id' => 'acme',
    ]);

    $other = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $outsider->id,
        'company_id' => 'globex',
    ]);

    filament()->setCurrentPanel(filament()->getPanel('user'));
    $this->actingAs($me);

    livewire(ListTickets::class)
        ->assertCanSeeTableRecords([$mine, $colleagues])
        ->assertCanNotSeeTableRecords([$other]);
});

it('still falls back to the requester\'s own tickets when the user resolves no company', function () {
    $me = UserFactory::new()->create(['company_id' => null]);
    $other = UserFactory::new()->create(['company_id' => null]);

    $mine = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $me->id,
    ]);

    $otherTicket = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $other->id,
    ]);

    filament()->setCurrentPanel(filament()->getPanel('user'));
    $this->actingAs($me);

    livewire(ListTickets::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$otherTicket]);
});

it('lets a user open and comment on a colleague\'s ticket from the same company', function () {
    $me = UserFactory::new()->create(['company_id' => 'acme']);
    $colleague = UserFactory::new()->create(['company_id' => 'acme']);

    $ticket = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $colleague->id,
        'company_id' => 'acme',
    ]);

    filament()->setCurrentPanel(filament()->getPanel('user'));
    $this->actingAs($me);

    livewire(ViewTicket::class, ['record' => $ticket->uuid])
        ->assertSuccessful()
        ->set('commentData.body', 'Following up on this one.')
        ->call('submitComment')
        ->assertNotified();

    expect($ticket->comments()->count())->toBe(1);
});

it('404s a ticket belonging to another company', function () {
    $me = UserFactory::new()->create(['company_id' => 'acme']);
    $outsider = UserFactory::new()->create(['company_id' => 'globex']);

    $ticket = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $outsider->id,
        'company_id' => 'globex',
    ]);

    filament()->setCurrentPanel(filament()->getPanel('user'));
    $this->actingAs($me);

    assertLivewireNotFound(fn () => livewire(ViewTicket::class, ['record' => $ticket->uuid]));
});

it('stamps the ticket with the requester\'s company id on creation', function () {
    $me = UserFactory::new()->create(['company_id' => 'acme']);

    filament()->setCurrentPanel(filament()->getPanel('user'));
    $this->actingAs($me);

    livewire(CreateTicket::class)
        ->fillForm([
            'department_id' => $this->department->id,
            'title' => 'Printer on fire',
            'description' => 'Smells like burnt toast.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->where('title', 'Printer on fire')->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->company_id)->toBe('acme');
});

it('leaves company_id null when the requester resolves no company', function () {
    $me = UserFactory::new()->create(['company_id' => null]);

    filament()->setCurrentPanel(filament()->getPanel('user'));
    $this->actingAs($me);

    livewire(CreateTicket::class)
        ->fillForm([
            'department_id' => $this->department->id,
            'title' => 'Keyboard missing a key',
            'description' => 'The J key fell off.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->where('title', 'Keyboard missing a key')->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->company_id)->toBeNull();
});
