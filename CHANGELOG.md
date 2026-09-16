# Changelog

All notable changes to `filament-help-desk` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.2.1 - 2026-09-16

Documentation only. No code changed since the previous release — upgrading is optional.

### Setting up satellite applications with one central panel

The README covered what the panels show once several applications share a Help Desk database, but not how to arrange the applications in the first place. A new setup section does, starting with the one decision the core package cannot document because it is about panels rather than data: which plugin belongs in which application.

| | Satellite application | Central application |
| --- | --- | --- |
| Plugins | `FilamentHelpDeskUserPlugin` | `FilamentHelpDeskOperatorPlugin`, `FilamentHelpDeskAdminPlugin` |
| Help desk migrations | never runs them | owns the schema, runs them |
| `HELPDESK_APP_KEY` | its own key | unset |
| `HELPDESK_SCOPE_TO_APP` | `true` | `false` |

It also spells out the four things that break the panels when missed:

- only the central application may run the help desk migrations, since Laravel records applied migrations in each application's own default connection
- every application needs its own morph alias, or the requester keys collide
- attachments need a shared disk: `attachment_disk` defaults to `local`, which leaves each upload on the disk of whichever application received it, so the central panel cannot serve a file a satellite stored
- `HELPDESK_SCOPE_TO_APP` belongs on the satellites only — leave it on centrally and the Admin and Operator panels see nothing

See [Sharing One Help Desk Database Across Applications](https://github.com/jeffersongoncalves/laravel-help-desk#sharing-one-help-desk-database-across-applications) in the core package for the data side of the same topology.

## 2.2.0 - 2026-09-16

Everything a Help Desk database shared by several applications needs the panels to do. Requires `jeffersongoncalves/laravel-help-desk` `^1.5`.

### Tickets from other applications no longer take the page down

The panels read the requester and the comment author straight off a `morphTo` relation. When a ticket comes from an application whose user model this one does not have installed, that is not a blank cell — Eloquent instantiates the stored class name and the request dies:

```
Class "satellite-app-user" not found
  at MorphTo::createModelByType()


```
The ticket list, the ticket detail page and the comment timeline all went down together. Reads now go through `requester_name` and `author_name`, which return the live model where its class exists here and the identity snapshot in `metadata` where it does not. The comment timeline also stopped eager loading the author, since eager loading a `morphTo` instantiates every stored type up front.

### Morph aliases are honoured on both sides

The requester and the assigned operator were keyed with `get_class()`, which silently bypasses any registered morph map. `Relation::enforceMorphMap()` had no effect on either the write or the read side, so applications sharing one database collided on the same `(user_type, user_id)` pair and each one's users could see the other's tickets.

Every read and write now goes through `getMorphClass()`, matching what `laravel-help-desk`'s own services already store — including `assigned_to_type` written from `config('help-desk.models.operator')`.

With no morph map registered, `getMorphClass()` returns the class name, so single-application installs are unaffected and no data migration is needed. Where several applications share a database, give each one its own alias; the README has the details.

### Which application a ticket came from

Set a key and a label per application:

```dotenv
HELPDESK_APP_KEY=app-a
HELPDESK_APP_NAME="Application A"


```
The Admin and Operator ticket tables gain an **Application** column and a filter, and the ticket detail view names the originating application. The label travels with the ticket, since the central application has no configuration describing the others, and falls back to the raw key.

Both are built from the keys actually present, so an install where no ticket carries one sees neither. The User panel never shows it — every ticket a requester sees comes from the application they are already in.

### Attachment uploaders carry a snapshot

`laravel-help-desk` 1.5 writes one in its `AttachmentService`, but the panels create `TicketAttachment` through the model, so attachments uploaded from them carried none. All five creation sites now pass the uploader to `TicketAttachment::snapshotOf()`. Nothing displays the uploader yet; the value is there for whatever does.

### The test suite runs again

Every feature test was failing with `ViewErrorBag::put(): Argument #2 must be of type MessageBag, null given`. The harness registered Livewire's service provider before Filament's, the opposite of what Laravel's package discovery produces, and Filament's `SupportServiceProvider` rebinds Livewire's `DataStore` with a non-shared binding — so every component resolved a throwaway store and `getErrorBag()` returned null. Registering the `filament/*` providers first fixes it, along with the sub-package providers whose absence made views resolve to `No hint path defined for [filament-tables]`.

CI now pins **PHP 8.4 and Laravel 13** across every branch, and PHPStan runs on 8.4 rather than 8.3.

### Upgrading

```bash
composer update jeffersongoncalves/filament-help-desk


```
No migration and no configuration change. The new column, filter and detail entry appear only once tickets carry an app key.
