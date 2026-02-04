<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskServiceProvider;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskUserPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
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

    protected function registerTestPanels(): void
    {
        $userPanel = Panel::make()
            ->default()
            ->id('user')
            ->path('user')
            ->login()
            ->plugin(FilamentHelpDeskUserPlugin::make());

        $operatorPanel = Panel::make()
            ->id('operator')
            ->path('operator')
            ->login()
            ->plugin(FilamentHelpDeskOperatorPlugin::make());

        $adminPanel = Panel::make()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(FilamentHelpDeskAdminPlugin::make());

        filament()->registerPanel($userPanel);
        filament()->registerPanel($operatorPanel);
        filament()->registerPanel($adminPanel);
    }
}
