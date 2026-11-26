<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ViewTicket as AdminViewTicket;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\ViewTicket as OperatorViewTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket as UserViewTicket;
use JeffersonGoncalves\HelpDesk\Services\CommentService;

use function Pest\Livewire\livewire;

it('sanitizes comment bodies on the ticket page', function (string $panel, string $page) {
    $requester = UserFactory::new()->create();

    $ticket = TicketFactory::new()->create([
        'department_id' => DepartmentFactory::new()->create()->id,
        'user_type' => User::class,
        'user_id' => $requester->id,
    ]);

    app(CommentService::class)->addReply(
        $ticket,
        $requester,
        'Safe text <script>alert(1)</script><img src="x" onerror="alert(2)">',
    );

    filament()->setCurrentPanel(filament()->getPanel($panel));
    $this->actingAs($requester);

    $html = livewire($page, ['record' => $ticket->uuid])
        ->assertSuccessful()
        ->assertSee('Safe text')
        ->html();

    expect($html)->not->toContain('<script>alert(1)')
        ->and($html)->not->toContain('onerror');
})->with([
    'admin' => ['admin', AdminViewTicket::class],
    'operator' => ['operator', OperatorViewTicket::class],
    'user' => ['user', UserViewTicket::class],
]);
