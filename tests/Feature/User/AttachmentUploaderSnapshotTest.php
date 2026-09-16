<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\CreateTicket;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\ViewTicket;
use JeffersonGoncalves\HelpDesk\Models\TicketAttachment;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Storage::fake('local');

    $this->user = UserFactory::new()->create(['name' => 'Ada Lovelace']);
    $this->department = DepartmentFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    $this->actingAs($this->user);
});

// The plugin creates TicketAttachment through the model rather than through
// AttachmentService, so it has to write the uploader snapshot itself.

it('snapshots the uploader of an attachment added while opening a ticket', function () {
    livewire(CreateTicket::class)
        ->fillForm([
            'title' => 'Ticket with an attachment',
            'description' => 'Ticket description.',
            'department_id' => $this->department->id,
            'priority' => 'medium',
            'attachments' => [UploadedFile::fake()->create('report.pdf', 8)],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $attachment = TicketAttachment::query()->latest('id')->first();

    expect($attachment)->not->toBeNull()
        ->and(data_get($attachment->metadata, 'uploader.name'))->toBe('Ada Lovelace')
        ->and($attachment->uploader_name)->toBe('Ada Lovelace');
});

it('snapshots the uploader of an attachment added to a comment', function () {
    $ticket = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    livewire(ViewTicket::class, ['record' => $ticket->uuid])
        ->fillForm([
            'body' => 'A reply carrying a file.',
            'attachments' => [UploadedFile::fake()->create('reply.pdf', 8)],
        ], 'commentForm')
        ->call('submitComment');

    $attachment = TicketAttachment::query()->latest('id')->first();

    expect($attachment)->not->toBeNull()
        ->and($attachment->comment_id)->not->toBeNull()
        ->and(data_get($attachment->metadata, 'uploader.name'))->toBe('Ada Lovelace')
        ->and($attachment->uploader_name)->toBe('Ada Lovelace');
});
