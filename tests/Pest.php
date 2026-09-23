<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use JeffersonGoncalves\FilamentHelpDesk\Tests\ApiTestCase;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\Tests\TestCase;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

uses(TestCase::class)->in('Unit', 'Feature');
uses(ApiTestCase::class)->in('ApiDriver');

/**
 * Assert that mounting a Livewire page answers 404.
 *
 * Livewire up to 4.4.5 rethrows the ModelNotFoundException in tests; 4.4.6+
 * renders it through the exception handler, so the test gets the 404 page.
 */
function assertLivewireNotFound(Closure $mount): void
{
    try {
        $testable = $mount();
    } catch (ModelNotFoundException) {
        expect(true)->toBeTrue();

        return;
    }

    expect(\Livewire\invade($testable)->lastState->getResponse()->getStatusCode())->toBe(404);
}

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

/**
 * A ticket as the API resource publishes it: uuid and no id, the requester as
 * a flat snapshot, and no department or category beyond their ids.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function apiTicketPayload(array $overrides = []): array
{
    return array_merge([
        'uuid' => (string) Str::uuid(),
        'reference_number' => 'TKT-0001',
        'department_id' => 1,
        'category_id' => null,
        'title' => 'Scanner will not feed',
        'description' => '<p>It jams on the second page.</p>',
        'status' => 'open',
        'priority' => 'medium',
        'source' => 'api',
        'app_key' => 'app-a',
        'requester_name' => 'Ada Lovelace',
        'requester_email' => 'ada@example.com',
        'closed_at' => null,
        'due_at' => null,
        'last_replied_at' => null,
        'created_at' => now()->toIso8601String(),
        'updated_at' => now()->toIso8601String(),
    ], $overrides);
}

/**
 * The paginated envelope GET tickets answers with.
 *
 * @param  array<int, array<string, mixed>>  $tickets
 * @return array<string, mixed>
 */
function apiTicketList(array $tickets, int $perPage = 25, int $page = 1): array
{
    return [
        'data' => $tickets,
        'meta' => [
            'total' => count($tickets),
            'per_page' => $perPage,
            'current_page' => $page,
        ],
    ];
}
