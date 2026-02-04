<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource\Pages\ListEmailChannels;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);
});

it('can render the email channel list page', function () {
    livewire(ListEmailChannels::class)
        ->assertSuccessful();
});
