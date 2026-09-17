<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\FilamentHelpDesk\Http\Controllers\DownloadTicketAttachmentController;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;

class FilamentHelpDeskUserPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-help-desk-user';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            config('filament-help-desk.user.resource', TicketResource::class),
        ]);

        $panel->widgets(config('filament-help-desk.user.widgets', []));

        // Registered on the panel rather than as a package route, so it
        // inherits the panel's own authentication: whoever may see the ticket
        // page is whoever may download from it.
        $panel->authenticatedRoutes(function (): void {
            Route::get(
                'help-desk/attachments/{ticket}/{attachment}',
                DownloadTicketAttachmentController::class,
            )->name('filament-help-desk.attachments.download');
        });
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
