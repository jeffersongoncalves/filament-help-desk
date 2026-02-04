---
name: Filament Help Desk Development
description: Skill for developing with and extending the filament-help-desk package
---

# When to Use This Skill

Use this skill when:
- Setting up the filament-help-desk package in a Laravel application
- Customizing ticket forms, tables, or views
- Extending resources or creating custom pages
- Integrating help desk functionality into existing Filament panels
- Troubleshooting plugin registration issues

# Installation

```bash
composer require jeffersongoncalves/filament-help-desk:^1.0
```

Publish and run migrations from the base package:
```bash
php artisan vendor:publish --tag="help-desk-migrations"
php artisan migrate
```

Publish the config:
```bash
php artisan vendor:publish --tag="filament-help-desk-config"
```

# Setup

## 1. Add traits to your User model

```php
use JeffersonGoncalves\HelpDesk\Concerns\HasTickets;
use JeffersonGoncalves\HelpDesk\Concerns\IsOperator;

class User extends Authenticatable
{
    use HasTickets;
    use IsOperator; // Only for operator/admin users
}
```

## 2. Register plugins in your panels

```php
// In your UserPanelProvider
->plugins([
    FilamentHelpDeskUserPlugin::make(),
])

// In your AdminPanelProvider
->plugins([
    FilamentHelpDeskAdminPlugin::make(),
    FilamentHelpDeskOperatorPlugin::make(), // Can combine admin + operator
])
```

# Creating Tickets Programmatically

```php
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;

$ticket = HelpDesk::create([
    'title' => 'Issue with login',
    'description' => 'Cannot access my account',
    'department_id' => 1,
    'priority' => 'high',
], $user);
```

# Customizing Forms

Override the form schema by extending the resource:

```php
namespace App\Filament\User\Resources;

use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource as BaseResource;

class TicketResource extends BaseResource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Your custom form schema
            ...static::getTicketFormSchema(isUser: true),
            // Add custom fields
            Forms\Components\Select::make('custom_field')
                ->options([...]),
        ]);
    }
}
```

Then update the config:
```php
'user' => [
    'resource' => \App\Filament\User\Resources\TicketResource::class,
],
```

# Customizing Tables

Similarly, extend the resource and override the table method using the shared trait columns as a base.

# Adding Custom Widgets

Register additional widgets by extending the plugin or adding them directly to your panel.

# Examples

## Custom ticket creation with attachments
```php
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;

$ticket = HelpDesk::create([
    'title' => 'Bug report',
    'description' => 'Steps to reproduce...',
    'department_id' => $departmentId,
    'category_id' => $categoryId,
    'priority' => 'urgent',
], auth()->user());

// Add a comment
HelpDesk::comments()->addReply($ticket, auth()->user(), 'Additional details...', [
    'attachments' => $request->file('files'),
]);
```

## Assigning tickets
```php
HelpDesk::assign($ticket, $operator, auth()->user());
```

## Changing status
```php
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;

HelpDesk::changeStatus($ticket, TicketStatus::InProgress, auth()->user());
```
