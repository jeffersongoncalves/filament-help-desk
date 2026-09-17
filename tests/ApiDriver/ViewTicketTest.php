<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket;

use function Pest\Livewire\livewire;

function apiShowResponse(string $uuid, array $comments = [], array $attachments = []): array
{
    return [
        'data' => apiTicketPayload([
            'uuid' => $uuid,
            'comments' => $comments,
            'attachments' => $attachments,
        ]),
    ];
}

function apiCommentPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'body' => '<p>We have logged it with the vendor.</p>',
        'type' => 'reply',
        'author_name' => 'Grace Hopper',
        'created_at' => now()->toIso8601String(),
    ], $overrides);
}

function apiAttachmentPayload(array $overrides = []): array
{
    return array_merge([
        'uuid' => 'b1c0f4c6-0c2a-4f1e-9b55-3f6d6f1f0aa1',
        'comment_id' => null,
        'file_name' => 'jam-photo.png',
        'mime_type' => 'image/png',
        'file_size' => 2048,
        'uploader_name' => 'Ada Lovelace',
        'created_at' => now()->toIso8601String(),
    ], $overrides);
}

it('shows the ticket, its comments and its attachments from one response', function (): void {
    $uuid = '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11';

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}*" => Http::response(apiShowResponse(
            $uuid,
            comments: [apiCommentPayload(['body' => '<p>We have logged it with the vendor.</p>'])],
            attachments: [
                apiAttachmentPayload(['file_name' => 'jam-photo.png']),
                apiAttachmentPayload([
                    'uuid' => 'c2d1e5f7-1d3b-4e2f-8c66-4a7e7e2f1bb2',
                    'comment_id' => 1,
                    'file_name' => 'vendor-reply.pdf',
                ]),
            ],
        )),
    ]);

    $this->actingAs(UserFactory::new()->create());

    livewire(ViewTicket::class, ['record' => $uuid])
        ->assertOk()
        ->assertSee('TKT-0001')
        ->assertSee('Grace Hopper')
        // Hung off the ticket…
        ->assertSee('jam-photo.png')
        // …and off the comment it was sent with, from the same flat list.
        ->assertSee('vendor-reply.pdf');
});

it('links an attachment at the package route rather than the disk', function (): void {
    $uuid = '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11';

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}*" => Http::response(apiShowResponse(
            $uuid,
            attachments: [apiAttachmentPayload()],
        )),
    ]);

    $this->actingAs(UserFactory::new()->create());

    // getUrl() throws on a satellite on purpose, so a rendered page is already
    // evidence it was not called — but the route is what must be there.
    livewire(ViewTicket::class, ['record' => $uuid])
        ->assertOk()
        ->assertSee("help-desk/attachments/{$uuid}/".apiAttachmentPayload()['uuid'], escape: false);
});

it('offers the requester their own two status changes and nothing else', function (): void {
    $uuid = '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11';

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}*" => Http::response(apiShowResponse($uuid)),
    ]);

    $this->actingAs(UserFactory::new()->create());

    livewire(ViewTicket::class, ['record' => $uuid])
        ->assertOk()
        // Close and reopen work over the API since the base package's v1.8, so
        // they stay. Everything an operator does is absent, and the comment
        // form carries no internal-note toggle.
        ->assertActionVisible('close')
        ->assertActionHidden('reopen')
        ->assertDontSee('filament-help-desk::filament-help-desk.fields.internal_note')
        ->assertFormFieldDoesNotExist('is_internal', 'commentForm');
});

it('closes a ticket through the status endpoint', function (): void {
    $uuid = '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11';

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}/status" => Http::response([
            'data' => apiTicketPayload(['uuid' => $uuid, 'status' => 'closed']),
        ]),
        "*/help-desk/api/tickets/{$uuid}*" => Http::response(apiShowResponse($uuid)),
    ]);

    $this->actingAs(UserFactory::new()->create());

    livewire(ViewTicket::class, ['record' => $uuid])
        ->callAction('close')
        ->assertOk();

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && str_contains($request->url(), "tickets/{$uuid}/status")
        && $request['status'] === 'closed');
});

it('returns a 404 for a ticket the central application will not serve', function (): void {
    $uuid = '00000000-0000-4000-8000-000000000000';

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}*" => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->actingAs(UserFactory::new()->create());

    livewire(ViewTicket::class, ['record' => $uuid]);
})->throws(ModelNotFoundException::class);
