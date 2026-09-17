<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket;

use function Pest\Livewire\livewire;

it('posts a reply over the API and shows it once the ticket is read back', function (): void {
    $uuid = '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11';
    $replied = false;

    $reply = [
        'id' => 2,
        'body' => '<p>Any update on this?</p>',
        'type' => 'reply',
        'author_name' => 'Ada Lovelace',
        'created_at' => now()->toIso8601String(),
    ];

    Http::fake([
        // Only the POST moves the central application forward. The show
        // endpoint below reports what it finds and never advances it, so the
        // reply can only reach the timeline through the re-read that
        // submitComment() does after posting — which is the thing under test.
        "*/help-desk/api/tickets/{$uuid}/comments" => function () use ($reply, &$replied) {
            $replied = true;

            return Http::response(['data' => $reply], 201);
        },
        // A normal closure, not an arrow function: an arrow function would
        // capture $replied by value and never see the POST land.
        "*/help-desk/api/tickets/{$uuid}*" => function () use ($uuid, $reply, &$replied) {
            return Http::response(['data' => apiTicketPayload([
                'uuid' => $uuid,
                'comments' => $replied ? [$reply] : [],
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
