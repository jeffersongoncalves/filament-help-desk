<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Panel;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;

class OperatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('operator')
            ->path('operator')
            ->login()
            ->plugin(FilamentHelpDeskOperatorPlugin::make())
            ->middleware([
                DispatchServingFilamentEvent::class,
                DisableBladeIconComponents::class,
            ]);
    }
}
