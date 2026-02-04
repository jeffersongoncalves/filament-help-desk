<?php

use Filament\Contracts\Plugin;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskUserPlugin;

test('user plugin has correct ID', function () {
    $plugin = FilamentHelpDeskUserPlugin::make();

    expect($plugin->getId())->toBe('filament-help-desk-user');
});

test('operator plugin has correct ID', function () {
    $plugin = FilamentHelpDeskOperatorPlugin::make();

    expect($plugin->getId())->toBe('filament-help-desk-operator');
});

test('admin plugin has correct ID', function () {
    $plugin = FilamentHelpDeskAdminPlugin::make();

    expect($plugin->getId())->toBe('filament-help-desk-admin');
});

test('user plugin implements Plugin interface', function () {
    $plugin = FilamentHelpDeskUserPlugin::make();

    expect($plugin)->toBeInstanceOf(Plugin::class);
});

test('operator plugin implements Plugin interface', function () {
    $plugin = FilamentHelpDeskOperatorPlugin::make();

    expect($plugin)->toBeInstanceOf(Plugin::class);
});

test('admin plugin implements Plugin interface', function () {
    $plugin = FilamentHelpDeskAdminPlugin::make();

    expect($plugin)->toBeInstanceOf(Plugin::class);
});

test('user plugin make() returns an instance', function () {
    $plugin = FilamentHelpDeskUserPlugin::make();

    expect($plugin)->toBeInstanceOf(FilamentHelpDeskUserPlugin::class);
});

test('operator plugin make() returns an instance', function () {
    $plugin = FilamentHelpDeskOperatorPlugin::make();

    expect($plugin)->toBeInstanceOf(FilamentHelpDeskOperatorPlugin::class);
});

test('admin plugin make() returns an instance', function () {
    $plugin = FilamentHelpDeskAdminPlugin::make();

    expect($plugin)->toBeInstanceOf(FilamentHelpDeskAdminPlugin::class);
});
