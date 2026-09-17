<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Panel;

use Filament\Http\Middleware\Authenticate;
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
            ])
            // The package registers the attachment download on the panel so it
            // inherits this: whoever may see the ticket may download from it.
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
