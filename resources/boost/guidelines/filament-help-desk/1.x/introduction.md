# Filament Help Desk Plugin

## Overview
The `jeffersongoncalves/filament-help-desk` package provides three Filament plugins that serve as the UI layer for `jeffersongoncalves/laravel-help-desk`:

- **FilamentHelpDeskUserPlugin** - End-user ticket submission and tracking
- **FilamentHelpDeskOperatorPlugin** - Support agent ticket management
- **FilamentHelpDeskAdminPlugin** - System administration and configuration

## Architecture

The package follows a plugin-per-panel pattern. Each plugin registers its own resources and widgets into a Filament Panel.

### Plugin Registration

Register plugins in your Filament panel providers:

```php
// User Panel
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskUserPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentHelpDeskUserPlugin::make(),
        ]);
}

// Operator Panel
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentHelpDeskOperatorPlugin::make(),
        ]);
}

// Admin Panel
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentHelpDeskAdminPlugin::make(),
        ]);
}
```

## Customization Patterns

### Extending Resources
You can override the default resource classes in the config file `config/filament-help-desk.php`:

```php
'user' => [
    'resource' => \App\Filament\User\Resources\CustomTicketResource::class,
],
```

### Publishing Views
```bash
php artisan vendor:publish --tag="filament-help-desk-views"
```

### Publishing Translations
```bash
php artisan vendor:publish --tag="filament-help-desk-translations"
```

### Publishing Config
```bash
php artisan vendor:publish --tag="filament-help-desk-config"
```

## Shared Concerns

The package uses shared traits in `JeffersonGoncalves\FilamentHelpDesk\Concerns`:

- `HasTicketForm` - Reusable form schemas
- `HasTicketTable` - Reusable table columns and filters
- `HasTicketInfolist` - Reusable infolist entries
- `InteractsWithTicketComments` - Comment and reply logic

When creating custom resources, you can use these traits to maintain consistency.

## Relationship with laravel-help-desk

This package is a UI layer only. All business logic (services, events, notifications) comes from `jeffersongoncalves/laravel-help-desk`. Configuration for models, tickets, email, and notifications is managed through the base package's `config/help-desk.php`.
