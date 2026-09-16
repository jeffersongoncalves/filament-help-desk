# Filament Help Desk Plugin

## Overview

`jeffersongoncalves/filament-help-desk` is the UI layer for `jeffersongoncalves/laravel-help-desk`. It ships three independent Filament plugins:

- **FilamentHelpDeskUserPlugin** — end users submit and track their own tickets
- **FilamentHelpDeskOperatorPlugin** — support agents work the queue
- **FilamentHelpDeskAdminPlugin** — administration and configuration

All business logic — services, events, notifications — lives in the base package. This one registers resources, pages and widgets into panels.

## Plugin registration

```php
public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        FilamentHelpDeskUserPlugin::make(),
    ]);
}
```

Plugins combine freely: registering `FilamentHelpDeskAdminPlugin` and `FilamentHelpDeskOperatorPlugin` in one panel is the common admin setup.

## Shared concerns

Traits in `JeffersonGoncalves\FilamentHelpDesk\Concerns` back every panel, so a custom resource can reuse them:

```php
HasTicketForm::getTicketFormSchema(bool $isUser = false): array
HasTicketForm::getTicketEditFormSchema(): array
HasTicketTable::getTicketTableColumns(bool $showUser = true, bool $showApplication = false): array
HasTicketTable::getTicketTableFilters(bool $showApplication = false): array
HasTicketInfolist::getTicketInfolistSchema(bool $showApplication = false): array
InteractsWithTicketComments   // comment timeline and reply handling
```

`$showApplication` is passed as `true` only by the Admin and Operator resources. See *Several applications, one database* below.

## Never touch a polymorphic relation directly

Tickets, comments and attachments reference people through morph pairs (`user_type`/`user_id`, `author_type`/`author_id`, `uploaded_by_type`/`uploaded_by_id`). When several applications share one Help Desk database, a row can carry a morph type whose class this application does not have installed.

Reading that relation is **fatal**, not blank — Eloquent instantiates the stored class name:

```php
// WRONG: raises Class "satellite-app-user" not found
TextColumn::make('user.name')
$comment->author?->name
->with(['author', 'attachments'])   // eager loading a morphTo instantiates every stored type
```

Use the accessors the base package exposes. They return the live model when its class resolves here, and the identity snapshot kept in `metadata` when it does not:

```php
$ticket->requester_name;      // and requester_email, requester()
$comment->author_name;        // and author_email, resolvedAuthor()
$attachment->uploader_name;   // and uploader_email, resolvedUploadedBy()
$entry->performer_name;       // TicketHistory
$row->watcher_name;           // TicketWatcher
```

## Always write morph types through getMorphClass()

`get_class($user)` bypasses any registered morph map, so a `Relation::enforceMorphMap()` alias would be ignored on both the write and the read side:

```php
// WRONG
$data['user_type'] = get_class($user);
->where('assigned_to_type', get_class($user))

// RIGHT
$data['user_type'] = $user->getMorphClass();
->where('assigned_to_type', $user->getMorphClass())

// For a model resolved from config, instantiate it to ask
$operatorModel = config('help-desk.models.operator');
$data['assigned_to_type'] = (new $operatorModel)->getMorphClass();
```

## Snapshot the uploader when creating an attachment directly

`AttachmentService` writes the identity snapshot itself, but the panels create `TicketAttachment` through the model. Those call sites must pass the uploader they are already holding:

```php
TicketAttachment::create([
    // ...
    'uploaded_by_type' => $user->getMorphClass(),
    'uploaded_by_id' => $user->getKey(),
    'metadata' => ['uploader' => TicketAttachment::snapshotOf($user)],
]);
```

History entries and watchers need no such care: every call goes through `TicketService` with a `performer:` argument, so the base package snapshots them.

## Several applications, one database

Satellite applications expose only the end-user side; one central application runs the queue and the administration.

| | Satellite | Central |
| --- | --- | --- |
| Plugins | `FilamentHelpDeskUserPlugin` | `FilamentHelpDeskOperatorPlugin`, `FilamentHelpDeskAdminPlugin` |
| Help desk migrations | never runs them | owns the schema |
| `HELPDESK_APP_KEY` | its own key | unset |
| `HELPDESK_SCOPE_TO_APP` | `true` | `false` |

Every application needs its own morph alias, or requester keys collide:

```php
// AppServiceProvider::boot()
Relation::enforceMorphMap(['app-a-user' => User::class]);
```

Attachments need a shared disk — `help-desk.ticket.attachment_disk` defaults to `local`, which leaves each upload on the disk of whichever application received it.

When tickets carry an app key, the Admin and Operator tables show an **Application** column and filter, and the detail view names the originating application, all reading `$ticket->app_name`. Where no ticket carries one, none of it appears.

## Customization

Point the config at your own class:

```php
// config/filament-help-desk.php
'user' => [
    'resource' => \App\Filament\User\Resources\CustomTicketResource::class,
],
```

```php
class CustomTicketResource extends BaseResource
{
    public static function table(Table $table): Table
    {
        return $table->columns([
            ...static::getTicketTableColumns(showUser: false),
            TextColumn::make('custom_field'),
        ]);
    }
}
```

Publishing:

```bash
php artisan vendor:publish --tag="filament-help-desk-config"
php artisan vendor:publish --tag="filament-help-desk-views"
php artisan vendor:publish --tag="filament-help-desk-translations"
```
