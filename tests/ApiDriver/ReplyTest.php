<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket;

use function Pest\Livewire\livewire;

it('posts a reply over the API and shows it once the ticket is read back', function (): void {
    $uuid = '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11';
    $replied = false;

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}/comments" => Http::response([
            'data' => [
                'id' => 2,
                'body' => '<p>Any update on this?</p>',
                'type' => 'reply',
                'author_name' => 'Ada Lovelace',
                'created_at' => now()->toIso8601String(),
            ],
        ], 201),
        "*/help-desk/api/tickets/{$uuid}*" => function () use ($uuid, &$replied) {
            // The show response is the only place the timeline reads from, so
            // the reply has to come back from the central application before
            // it can appear — which is what the page re-reads it for.
            $comments = $replied
                ? [[
                    'id' => 2,
                    'body' => '<p>Any update on this?</p>',
                    'type' => 'reply',
                    'author_name' => 'Ada Lovelace',
                    'created_at' => now()->toIso8601String(),
                ]]
                : [];

            $replied = true;

            return Http::response(['data' => apiTicketPayload([
                'uuid' => $uuid,
                'comments' => $comments,
                'attachments' => [],
            ])]);
        },
    ]);

    $this->actingAs(UserFactory::new()->create());

    livewire(ViewTicket::class, ['record' => $uuid])
        ->fillForm(['body' => '<p>Any update on this?</p>'], 'commentForm')
        ->call('submitComment')
        ->assertOk()
        ->assertSee('Any update on this?', escape: false);

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && str_contains($request->url(), "tickets/{$uuid}/comments")
        && $request['body'] === '<p>Any update on this?</p>');
});
