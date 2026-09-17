<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\StoresTicketAttachments;
use JeffersonGoncalves\FilamentHelpDesk\Driver;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

/**
 * The trait's method is protected, as it should be — it is page plumbing, not
 * API. Exercising it through a bare user of the trait keeps the test about
 * what it does rather than about Livewire's file upload handling.
 */
function attachmentUploader(): object
{
    return new class
    {
        use StoresTicketAttachments;

        /**
         * @param  array<int, string>  $paths
         */
        public function run(Ticket $ticket, array $paths, $uploadedBy): void
        {
            $this->storeTicketAttachments($ticket, $paths, $uploadedBy);
        }
    };
}

it('sends an upload through the repository instead of writing it to a disk', function (): void {
    $uuid = '9f8e7d6c-5b4a-4938-8271-605f4e3d2c1b';

    Storage::fake('local');
    Storage::disk('local')->put('help-desk/attachments/report.pdf', 'the file bytes');

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}/attachments" => Http::response([
            'data' => [
                'uuid' => 'aa11bb22-cc33-4d44-8e55-ff6677889900',
                'comment_id' => null,
                'file_name' => 'report.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 14,
                'uploader_name' => 'Ada Lovelace',
                'created_at' => now()->toIso8601String(),
            ],
        ]),
    ]);

    $ticket = (new Ticket)->forceFill(['uuid' => $uuid]);

    attachmentUploader()->run(
        $ticket,
        ['help-desk/attachments/report.pdf'],
        UserFactory::new()->create(),
    );

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && str_contains($request->url(), "tickets/{$uuid}/attachments")
        && $request['file_name'] === 'report.pdf'
        && base64_decode($request['contents']) === 'the file bytes');

    // The upload was a staging copy on the way to the central application.
    // Keeping it would leave the satellite holding a full second copy of every
    // file it ever sent, on a disk it has no other use for.
    Storage::disk('local')->assertMissing('help-desk/attachments/report.pdf');
});

it('caps an upload at what the transport can carry inline', function (): void {
    // Deliberately lower than ticket.max_file_size: base64 grows the file by a
    // third and both ends hold it in memory.
    expect(Driver::maxAttachmentSize())->toBe(2048)
        ->and(config('help-desk.ticket.max_file_size', 10240))->toBeGreaterThan(2048);
});

it('serves an attachment through the package route', function (): void {
    $uuid = '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11';
    $attachmentUuid = 'b1c0f4c6-0c2a-4f1e-9b55-3f6d6f1f0aa1';

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}/attachments/{$attachmentUuid}*" => Http::response([
            'data' => ['contents' => base64_encode('the file bytes')],
        ]),
        "*/help-desk/api/tickets/{$uuid}*" => Http::response([
            'data' => apiTicketPayload([
                'uuid' => $uuid,
                'attachments' => [[
                    'uuid' => $attachmentUuid,
                    'comment_id' => null,
                    'file_name' => 'report.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 14,
                    'uploader_name' => 'Ada Lovelace',
                    'created_at' => now()->toIso8601String(),
                ]],
            ]),
        ]),
    ]);

    $this->actingAs(UserFactory::new()->create());

    $response = $this->get(route('filament.user.filament-help-desk.attachments.download', [
        'ticket' => $uuid,
        'attachment' => $attachmentUuid,
    ]));

    $response->assertOk()
        ->assertDownload('report.pdf');

    expect($response->streamedContent())->toBe('the file bytes');
});

it('does not serve an attachment the central application will not hand over', function (): void {
    $uuid = '00000000-0000-4000-8000-000000000000';

    Http::fake([
        "*/help-desk/api/tickets/{$uuid}*" => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->actingAs(UserFactory::new()->create());

    $this->get(route('filament.user.filament-help-desk.attachments.download', [
        'ticket' => $uuid,
        'attachment' => 'b1c0f4c6-0c2a-4f1e-9b55-3f6d6f1f0aa1',
    ]))->assertNotFound();
});

it('requires authentication', function (): void {
    $this->get(route('filament.user.filament-help-desk.attachments.download', [
        'ticket' => '5a4f0d5e-0f3c-4a1a-8f0e-2f7c0b6a1d11',
        'attachment' => 'b1c0f4c6-0c2a-4f1e-9b55-3f6d6f1f0aa1',
    ]))->assertRedirect();
});
