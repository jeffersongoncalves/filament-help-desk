<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskServiceProvider;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Panel\UserPanelProvider;
use JeffersonGoncalves\HelpDesk\HelpDeskServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

/**
 * A satellite application: the User panel, the API driver, and no help desk
 * tables at all.
 *
 * The missing tables are the point. Migrating them here would let a query slip
 * back into the panel and still pass, which is exactly the regression this
 * suite exists to catch — so only the users table is created, because the panel
 * still has to authenticate someone.
 *
 * The Admin and Operator panels are left unregistered for the same reason they
 * throw under this driver: there is nothing for them to read.
 */
class ApiTestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'JeffersonGoncalves\\FilamentHelpDesk\\Tests\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            TablesServiceProvider::class,
            ActionsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            WidgetsServiceProvider::class,
            HelpDeskServiceProvider::class,
            FilamentHelpDeskServiceProvider::class,
            UserPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $migration = include __DIR__.'/database/migrations/0000_00_00_000000_create_users_table.php';

        $migration->up();
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('help-desk.driver', 'api');
        config()->set('help-desk.app.key', 'app-a');
        config()->set('help-desk.api.url', 'https://support.example.com');
        config()->set('help-desk.api.secret', 'a-long-random-string-for-tests');

        config()->set('help-desk.models.user', User::class);
        config()->set('help-desk.models.operator', User::class);
        config()->set('help-desk.register_default_listeners', false);

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
