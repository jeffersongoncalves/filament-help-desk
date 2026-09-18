<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\CreateTicket;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->uuid = '7c3d9b2a-4e5f-4a6b-9c8d-1e2f3a4b5c6d';

    Http::fake([
        '*/help-desk/api/departments/1/categories*' => Http::response(['data' => [
            ['id' => 5, 'department_id' => 1, 'parent_id' => null, 'name' => 'Hardware', 'slug' => 'hardware'],
        ]]),
        '*/help-desk/api/departments*' => Http::response(['data' => [
            ['id' => 1, 'name' => 'Support', 'slug' => 'support'],
        ]]),
        "*/help-desk/api/tickets/{$this->uuid}*" => Http::response([
            'data' => apiTicketPayload(['uuid' => $this->uuid, 'comments' => [], 'attachments' => []]),
        ]),
        '*/help-desk/api/tickets' => Http::response([
            'data' => apiTicketPayload(['uuid' => $this->uuid]),
        ], 201),
    ]);

    $this->actingAs(UserFactory::new()->create());
});

it('offers the departments and categories the central application serves', function (): void {
    livewire(CreateTicket::class)
        ->assertOk()
        ->assertFormFieldExists('department_id', checkFieldUsing: fn ($field): bool => $field->getOptions() === [1 => 'Support'])
        ->fillForm(['department_id' => 1])
        ->assertFormFieldExists('category_id', checkFieldUsing: fn ($field): bool => $field->getOptions() === [5 => 'Hardware']);
});

it('opens a ticket over the API', function (): void {
    livewire(CreateTicket::class)
        ->fillForm([
            'department_id' => 1,
            'title' => 'Scanner will not feed',
            'description' => '<p>It jams on the second page.</p>',
            'priority' => 'medium',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && str_ends_with($request->url(), '/help-desk/api/tickets')
        && $request['title'] === 'Scanner will not feed'
        // The requester travels as the signed actor, not as a user_id the
        // satellite made up, and the repository is what stamps it.
        && isset($request['actor']['type'], $request['actor']['id']));
});

it('never wires up knowledge base deflection on a satellite', function (): void {
    // CoreKnowledgeBaseProvider (bound by default) queries KbArticle
    // directly against this application's own database — a satellite has
    // no help_desk_kb_articles table at all, so typing a title here must
    // never trigger a search. Regression test for a real failure: this
    // used to throw "no such table" the moment the title field was filled.
    livewire(CreateTicket::class)
        ->fillForm([
            'department_id' => 1,
            'title' => 'Scanner will not feed',
        ])
        ->assertOk()
        ->assertDontSee(__('filament-help-desk::filament-help-desk.deflection.heading'));
});
