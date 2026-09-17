<?php

use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\ViewTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket as UserViewTicket;
use JeffersonGoncalves\HelpDesk\Models\TicketComment;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->operator = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('operator'));

    $this->actingAs($this->operator);
});

it('can render the operator view ticket page', function () {
    $department = DepartmentFactory::new()->create();
    $user = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $user->id,
    ]);

    livewire(ViewTicket::class, [
        'record' => $ticket->uuid,
    ])
        ->assertSuccessful();
});

it('submits a public reply by default, visible to the requester', function () {
    $department = DepartmentFactory::new()->create();
    $requester = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
    ]);

    livewire(ViewTicket::class, ['record' => $ticket->uuid])
        ->fillForm(['body' => 'Public reply to the requester.'], 'commentForm')
        ->call('submitComment');

    $comment = TicketComment::query()->latest('id')->first();

    expect($comment)->not->toBeNull()
        ->and($comment->is_internal)->toBeFalse();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    livewire(UserViewTicket::class, ['record' => $ticket->uuid])
        ->assertSee('Public reply to the requester.');
});

it('submits a private note when toggled internal, hidden from the requester timeline', function () {
    $department = DepartmentFactory::new()->create();
    $requester = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => $department->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
    ]);

    livewire(ViewTicket::class, ['record' => $ticket->uuid])
        ->fillForm(['is_internal' => true, 'body' => 'Internal note, not for the requester.'], 'commentForm')
        ->call('submitComment');

    $comment = TicketComment::query()->latest('id')->first();

    expect($comment)->not->toBeNull()
        ->and($comment->is_internal)->toBeTrue();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    livewire(UserViewTicket::class, ['record' => $ticket->uuid])
        ->assertDontSee('Internal note, not for the requester.');
});
