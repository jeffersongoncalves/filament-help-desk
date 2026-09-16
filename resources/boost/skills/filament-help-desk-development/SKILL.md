---
name: Filament Help Desk Development
description: Skill for developing with and extending the filament-help-desk package
---

# When to Use This Skill

Use this skill when:
- Setting up the filament-help-desk package in a Laravel application
- Customizing ticket forms, tables, infolists or views
- Extending resources or creating custom pages
- Integrating help desk functionality into existing Filament panels
- Wiring several applications to one central Help Desk panel
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
// UserPanelProvider
->plugins([
    FilamentHelpDeskUserPlugin::make(),
])

// AdminPanelProvider
->plugins([
    FilamentHelpDeskAdminPlugin::make(),
    FilamentHelpDeskOperatorPlugin::make(), // admin + operator combine freely
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

HelpDesk::comments()->addReply($ticket, auth()->user(), 'Additional details...');
HelpDesk::assign($ticket, $operator, auth()->user());
HelpDesk::changeStatus($ticket, TicketStatus::InProgress, auth()->user());
```

# Reading People Off a Ticket

Tickets, comments and attachments reference people through morph pairs. When several applications share one Help Desk database, a row can carry a morph type whose class this application does not have installed — and reading the relation is fatal, not blank, because Eloquent instantiates the stored class name.

```php
// WRONG: raises Class "satellite-app-user" not found
TextColumn::make('user.name')
$comment->author?->name
->with(['author', 'attachments'])   // eager loading a morphTo instantiates every stored type

// RIGHT: live model where it resolves, identity snapshot where it does not
$ticket->requester_name;      // requester_email, requester()
$comment->author_name;        // author_email, resolvedAuthor()
$attachment->uploader_name;   // uploader_email, resolvedUploadedBy()
```

Write the type side through `getMorphClass()`, never `get_class()`, so a registered morph map is honoured:

```php
$data['user_type'] = $user->getMorphClass();

$operatorModel = config('help-desk.models.operator');
$data['assigned_to_type'] = (new $operatorModel)->getMorphClass();
```

Creating a `TicketAttachment` through the model rather than `AttachmentService` means writing the snapshot yourself:

```php
TicketAttachment::create([
    // ...
    'metadata' => ['uploader' => TicketAttachment::snapshotOf($user)],
]);
```

# Several Applications, One Central Panel

Satellite applications expose only the end-user side; one central application runs the queue and the administration.

| | Satellite | Central |
| --- | --- | --- |
| Plugins | `FilamentHelpDeskUserPlugin` | `FilamentHelpDeskOperatorPlugin`, `FilamentHelpDeskAdminPlugin` |
| Help desk migrations | never runs them | owns the schema |
| `HELPDESK_APP_KEY` | its own key | unset |
| `HELPDESK_SCOPE_TO_APP` | `true` | `false` |

```dotenv
# Satellite
HELPDESK_DB_CONNECTION=help_desk
HELPDESK_APP_KEY=app-a
HELPDESK_APP_NAME="Application A"
HELPDESK_SCOPE_TO_APP=true
```

Four things decide whether it works:

- only the central application runs the help desk migrations — Laravel records them in each application's own default connection
- every application registers its own morph alias, or requester keys collide:
  `Relation::enforceMorphMap(['app-a-user' => User::class]);`
- attachments need a shared disk; `attachment_disk` defaults to `local`
- `HELPDESK_SCOPE_TO_APP` belongs on satellites only, or the central panels see nothing

Once tickets carry an app key, the Admin and Operator tables gain an **Application** column and filter and the detail view names the originating application. Where none do, nothing appears.

# Customizing Forms, Tables and Infolists

Extend the resource and reuse the shared traits:

```php
namespace App\Filament\User\Resources;

use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource as BaseResource;

class TicketResource extends BaseResource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            ...static::getTicketFormSchema(isUser: true),
            Select::make('custom_field')->options([...]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTicketTableColumns(showUser: false))
            ->filters(static::getTicketTableFilters());
    }
}
```

Signatures:

```php
getTicketFormSchema(bool $isUser = false): array
getTicketEditFormSchema(): array
getTicketTableColumns(bool $showUser = true, bool $showApplication = false): array
getTicketTableFilters(bool $showApplication = false): array
getTicketInfolistSchema(bool $showApplication = false): array
```

Then point the config at your class:

```php
'user' => [
    'resource' => \App\Filament\User\Resources\TicketResource::class,
],
```
