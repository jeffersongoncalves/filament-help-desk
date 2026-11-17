<?php

use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource as OperatorTicketResource;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource as UserTicketResource;

it('links requesters to the user panel and operators to the operator panel', function () {
    $requester = UserFactory::new()->create();
    $operator = UserFactory::new()->create();
    $ticket = TicketFactory::new()->withUser($requester)->create();

    expect($ticket->notificationUrl($requester))
        ->toBe(UserTicketResource::getUrl('view', ['record' => $ticket], panel: 'user'))
        ->toContain($ticket->uuid)
        ->and($ticket->notificationUrl($operator))
        ->toBe(OperatorTicketResource::getUrl('view', ['record' => $ticket], panel: 'operator'));
});
