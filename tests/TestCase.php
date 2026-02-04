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
use JeffersonGoncalves\FilamentHelpDesk\Tests\Panel\AdminPanelProvider;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Panel\OperatorPanelProvider;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Panel\UserPanelProvider;
use JeffersonGoncalves\HelpDesk\HelpDeskServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'JeffersonGoncalves\\FilamentHelpDesk\\Tests\\Factories\\' . class_basename($modelName) . 'Factory'
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
            OperatorPanelProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('help-desk.models.user', User::class);
        config()->set('help-desk.models.operator', User::class);
        config()->set('help-desk.register_default_listeners', false);

        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}
