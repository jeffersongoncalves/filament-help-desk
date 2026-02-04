<?php

use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource\Pages\ListEmailChannels;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    $panel = Panel::make()
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->plugin(FilamentHelpDeskAdminPlugin::make());

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    $this->actingAs($this->admin);
});

it('can render the email channel list page', function () {
    livewire(ListEmailChannels::class)
        ->assertSuccessful();
});
