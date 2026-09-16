<?php

use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\Tests\TestCase;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

uses(TestCase::class)->in('Unit', 'Feature');

/**
 * A ticket opened by another application sharing this help desk database.
 *
 * The creating hook stamps this application's own name, so the label of the
 * application the ticket came from is written afterwards, the way that
 * application would have written it.
 */
function ticketFromApp(int $departmentId, string $key, ?string $name, string $title): Ticket
{
    $ticket = TicketFactory::new()->create([
        'department_id' => $departmentId,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'title' => $title,
        'app_key' => $key,
    ]);

    $ticket->update([
        'metadata' => $name === null ? null : ['app' => ['name' => $name]],
    ]);

    return $ticket->fresh();
}
