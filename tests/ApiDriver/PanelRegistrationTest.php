<?php

use Filament\Facades\Filament;
use JeffersonGoncalves\FilamentHelpDesk\Exceptions\UnsupportedDriverException;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskUserPlugin;

it('refuses to register the Admin panel on a satellite', function (): void {
    FilamentHelpDeskAdminPlugin::make()->register(Filament::getCurrentOrDefaultPanel());
})->throws(UnsupportedDriverException::class, 'Admin panel cannot run on the [api] help desk driver');

it('refuses to register the Operator panel on a satellite', function (): void {
    FilamentHelpDeskOperatorPlugin::make()->register(Filament::getCurrentOrDefaultPanel());
})->throws(UnsupportedDriverException::class, 'Operator panel cannot run on the [api] help desk driver');

it('registers the User panel', function (): void {
    // Already registered by the panel provider this suite boots — if it threw,
    // nothing in this suite would run at all.
    expect(Filament::getCurrentOrDefaultPanel()->getPlugin(FilamentHelpDeskUserPlugin::make()->getId()))
        ->toBeInstanceOf(FilamentHelpDeskUserPlugin::class);
});
