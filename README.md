<div class="filament-hidden">

![Filament Help Desk](https://raw.githubusercontent.com/jeffersongoncalves/filament-help-desk/3.x/art/jeffersongoncalves-filament-help-desk.png)

</div>

# Filament Help Desk

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/filament-help-desk.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-help-desk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/filament-help-desk/tests.yml?branch=3.x&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/filament-help-desk/actions?query=workflow%3Atests+branch%3A3.x)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/filament-help-desk.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/filament-help-desk)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/filament-help-desk.svg?style=flat-square)](LICENSE.md)

Filament plugins for [jeffersongoncalves/laravel-help-desk](https://github.com/jeffersongoncalves/laravel-help-desk) — providing User, Operator, and Admin panels for ticket management.

## Version Compatibility

| Plugin Version | Filament | Laravel | PHP |
|---------------|----------|---------|-----|
| 1.x | ^3.0 | ^10 \| ^11 \| ^12 | ^8.1 |
| 2.x | ^4.0 | ^11.0 | ^8.2 |
| 3.x | ^5.0 | ^11.28 | ^8.3 |

## Requirements

- PHP `^8.3`
- Laravel `^11.28`
- Filament `^5.0`
- [jeffersongoncalves/laravel-help-desk](https://github.com/jeffersongoncalves/laravel-help-desk) `^1.0`

## Installation

You can install the package via composer:

```bash
composer require jeffersongoncalves/filament-help-desk:^3.0
```

### Publish config (optional)

```bash
php artisan vendor:publish --tag="filament-help-desk-config"
```

### Publish views (optional)

```bash
php artisan vendor:publish --tag="filament-help-desk-views"
```

### Publish translations (optional)

```bash
php artisan vendor:publish --tag="filament-help-desk-translations"
```

> **Note:** Make sure you have already installed and configured [jeffersongoncalves/laravel-help-desk](https://github.com/jeffersongoncalves/laravel-help-desk) (migrations, config, etc.) before using this package.

## Setup

### 1. Add traits to your User model

```php
use JeffersonGoncalves\HelpDesk\Concerns\HasTickets;
use JeffersonGoncalves\HelpDesk\Concerns\IsOperator;

class User extends Authenticatable
{
    use HasTickets;
    use IsOperator; // Only needed for users who act as operators/admins
}
```

### 2. Register plugins in your Filament panels

This package provides **3 independent plugins** that can be registered in any combination across your panels:

#### User Plugin

For end-users to create and track their tickets.

```php
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskUserPlugin;

class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugins([
                FilamentHelpDeskUserPlugin::make(),
            ]);
    }
}
```

**Provides:**
- Ticket creation form (department, category, priority, attachments)
- Ticket listing with status/priority filters
- Ticket detail view with comment timeline and reply form
- Stats widget: open, pending, resolved, total tickets

#### Operator Plugin

For support agents to manage and respond to tickets.

```php
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskOperatorPlugin;

class OperatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugins([
                FilamentHelpDeskOperatorPlugin::make(),
            ]);
    }
}
```

**Provides:**
- Tabbed ticket queue (My Tickets, Unassigned, All)
- Ticket management: change status, priority, assign operators
- Internal notes and canned responses
- Tickets by status chart widget
- Assigned tickets table widget

#### Admin Plugin

For administrators to configure the help desk system.

```php
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugins([
                FilamentHelpDeskAdminPlugin::make(),
            ]);
    }
}
```

**Provides:**
- Department CRUD with operator management
- Category CRUD with hierarchical support
- Canned response CRUD
- Email channel configuration
- Full ticket management (all tickets, all statuses)
- Stats overview and priority distribution widgets

> **Tip:** You can combine plugins in a single panel. For example, register both `FilamentHelpDeskAdminPlugin` and `FilamentHelpDeskOperatorPlugin` in your admin panel.

## Configuration

The configuration file `config/filament-help-desk.php` allows you to customize:

```php
return [
    'user' => [
        'resource' => \JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource::class,
        'widgets' => [
            \JeffersonGoncalves\FilamentHelpDesk\User\Widgets\UserTicketStatsWidget::class,
        ],
        'navigation_group' => 'Support',
        'navigation_icon' => 'heroicon-o-ticket',
        'navigation_sort' => null,
        'slug' => 'tickets',
    ],
    'operator' => [
        'resource' => \JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource::class,
        'widgets' => [
            \JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\TicketsByStatusWidget::class,
            \JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\AssignedTicketsWidget::class,
        ],
        'navigation_group' => 'Help Desk',
        'navigation_icon' => 'heroicon-o-inbox-stack',
        'slug' => 'tickets',
    ],
    'admin' => [
        'navigation_group' => 'Help Desk',
        'navigation_icon' => 'heroicon-o-cog-6-tooth',
        'resources' => [
            'ticket' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource::class,
            'department' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource::class,
            'category' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource::class,
            'canned_response' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource::class,
            'email_channel' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource::class,
        ],
        'widgets' => [
            \JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketsByPriorityWidget::class,
            \JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketStatsOverviewWidget::class,
        ],
    ],
];
```

### Customizing Resources

Override any resource by pointing to your own class in the config:

```php
'user' => [
    'resource' => \App\Filament\User\Resources\CustomTicketResource::class,
],
```

Your custom resource can extend the default one:

```php
namespace App\Filament\User\Resources;

use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource as BaseResource;

class CustomTicketResource extends BaseResource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            ...static::getTicketFormSchema(isUser: true),
            // Add your custom fields
        ]);
    }
}
```

### Customizing Widgets

Each panel registers dashboard widgets that can be customized via the `widgets` config key. You can reorder, remove, or add your own widgets:

```php
'admin' => [
    'widgets' => [
        \JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketStatsOverviewWidget::class,
        // Remove TicketsByPriorityWidget by not including it
        \App\Filament\Widgets\CustomDashboardWidget::class, // Add your own
    ],
],
```

To disable all widgets for a panel, set it to an empty array:

```php
'user' => [
    'widgets' => [],
],
```

### Disabling Resources

You can disable any resource by removing or commenting out its key in the config file. The navigation item will automatically be hidden for any resource whose config key is not present.

For example, to disable the email channel resource in the admin panel:

```php
'admin' => [
    'resources' => [
        'ticket' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource::class,
        'department' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource::class,
        'category' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource::class,
        'canned_response' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource::class,
        // 'email_channel' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource::class,
    ],
],
```

To disable the entire User or Operator panel resource, set the `resource` key to `null`:

```php
'user' => [
    'resource' => null, // Disables the user ticket resource
    // ...
],
'operator' => [
    'resource' => null, // Disables the operator ticket resource
    // ...
],
```

## Translations

The package includes translations for:
- English (`en`)
- Brazilian Portuguese (`pt_BR`)

To add or modify translations, publish them and edit the files in `resources/lang/vendor/filament-help-desk/`.

## Testing

```bash
composer test
```

### PHPStan

```bash
composer analyse
```

### Code Style (Pint)

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
