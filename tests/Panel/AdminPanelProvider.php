<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Panel;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(FilamentHelpDeskAdminPlugin::make())
            ->middleware([
                DispatchServingFilamentEvent::class,
                DisableBladeIconComponents::class,
            ]);
    }
}
