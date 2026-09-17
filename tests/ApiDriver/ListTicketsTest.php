<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ListTickets;

use function Pest\Livewire\livewire;

it('lists every ticket the response carried', function (): void {
    Http::fake([
        '*/help-desk/api/tickets*' => Http::response(apiTicketList([
            apiTicketPayload(['reference_number' => 'TKT-0001', 'title' => 'Scanner will not feed']),
            apiTicketPayload(['reference_number' => 'TKT-0002', 'title' => 'Badge reader is dead']),
        ])),
    ]);

    $this->actingAs(UserFactory::new()->create());

    // Two rows, not one. Hydrated tickets used to key on a null id, so a whole
    // page collapsed into a single entry with nothing to show for it — worth
    // asserting on, because that failure is silent.
    livewire(ListTickets::class)
        ->assertOk()
        ->assertSee('TKT-0001')
        ->assertSee('TKT-0002');
});

it('asks the central application to filter rather than narrowing the page it holds', function (): void {
    Http::fake([
        '*/help-desk/api/tickets*' => Http::response(apiTicketList([
            apiTicketPayload(['reference_number' => 'TKT-0003', 'status' => 'closed']),
        ])),
    ]);

    $this->actingAs(UserFactory::new()->create());

    livewire(ListTickets::class)
        ->filterTable('status', 'closed')
        ->assertOk()
        ->assertSee('TKT-0003');

    Http::assertSent(fn ($request): bool => str_contains(urldecode($request->url()), 'status[0]=closed'));
});

it('sends the search term to the central application', function (): void {
    Http::fake([
        '*/help-desk/api/tickets*' => Http::response(apiTicketList([
            apiTicketPayload(['reference_number' => 'TKT-0004', 'title' => 'Scanner jam']),
        ])),
    ]);

    $this->actingAs(UserFactory::new()->create());

    livewire(ListTickets::class)
        ->searchTable('scanner')
        ->assertOk();

    Http::assertSent(fn ($request): bool => str_contains(urldecode($request->url()), 'q=scanner'));
});

it('never queries the tickets table', function (): void {
    Http::fake([
        '*/help-desk/api/tickets*' => Http::response(apiTicketList([apiTicketPayload()])),
    ]);

    $this->actingAs(UserFactory::new()->create());

    // There is no help_desk_tickets table in this application at all, so a
    // query would fail rather than return nothing. Rendering at all is the
    // assertion.
    livewire(ListTickets::class)->assertOk();
});
