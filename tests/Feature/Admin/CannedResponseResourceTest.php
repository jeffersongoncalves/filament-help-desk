<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource\Pages\CreateCannedResponse;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource\Pages\ListCannedResponses;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Models\CannedResponse;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);
});

it('can render the canned response list page', function () {
    livewire(ListCannedResponses::class)
        ->assertSuccessful();
});

it('can create a canned response', function () {
    livewire(CreateCannedResponse::class)
        ->fillForm([
            'title' => 'Welcome Response',
            'body' => '<p>Thank you for contacting us. We will review your request shortly.</p>',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(CannedResponse::query()->where('title', 'Welcome Response')->exists())->toBeTrue();
});
