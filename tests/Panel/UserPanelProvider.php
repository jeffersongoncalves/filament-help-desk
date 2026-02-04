<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Panel;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskUserPlugin;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('user')
            ->path('user')
            ->login()
            ->plugin(FilamentHelpDeskUserPlugin::make())
            ->middleware([
                DispatchServingFilamentEvent::class,
                DisableBladeIconComponents::class,
            ]);
    }
}
