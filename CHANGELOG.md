# Changelog

All notable changes to `filament-help-desk` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 3.5.0 - 2026-09-18

**Added:** "Test Connection" action on the EmailChannel form (Admin panel) — validates IMAP/webhook driver credentials before saving, via `laravel-help-desk` v1.11's `EmailDriver::testConnection()`. See #90.

**Fixed:** icon rendering (`x-heroicon-*` → `x-filament::icon`) so views don't break when a consuming app disables blade-icons component tags. See #84, #85.

**Docs:** Version Compatibility table now links each branch and reports the correct `laravel-help-desk` constraint (`^1.11`). See #97.

## 3.4.1 - 2026-09-18

**Fix:** `InvalidArgumentException: Unable to locate a class or view for component [heroicon-m-paper-clip]` on ticket attachment views, when a consuming app runs with `blade-icons.components.disabled = true`. Icons in the affected views now render through `<x-filament::icon>` instead of `<x-heroicon-*>` component tags. See #84, #85.

## 3.4.0 - 2026-09-17

The User panel runs on a satellite application now. Requires `jeffersongoncalves/laravel-help-desk` `^1.9`.

Nothing here changes `driver=database` behaviour, except one thing that is worth reading before upgrading — see **Attachment downloads** below.

### The User panel no longer assumes a database

An application that reaches a central help desk over the signed API has no help desk tables at all. Every read and write the User panel makes now goes through the repositories, so the same panel serves either transport:

```env
HELPDESK_DRIVER=api
HELPDESK_API_URL=https://support.example.com
HELPDESK_APP_KEY=app-a
HELPDESK_API_SECRET=a-long-random-string



```
- **The list** is fed by `forActor()`, and paged, filtered, searched and sorted by the central application. Not by narrowing the page the satellite happens to hold — a list that filters 25 rows out of 300 looks filtered and is wrong, with nothing on screen saying so.
- **A ticket** is resolved by uuid through the repository. One belonging to another user or another application comes back as a 404 without saying which.
- **The selects** come from `departments()->all()` and `->categoriesFor()`. Since the base package's v1.9 both return models on either driver, so there is no driver branch in the form at all.
- **The timeline** reads the comments and attachments the show response already carried, rather than re-querying. The `author` morph is still never eager-loaded.
- **Uploads** go through `storeFromPath()`, which is also what collapsed three hand-rolled copies of the same `Storage::move()` + `TicketAttachment::create()` block into one trait.

### Attachment downloads changed on both drivers

Attachments were linked with `$attachment->getUrl()` — a public disk URL with nothing checking who asked for it. They now go through a route registered on the panel, so they inherit the panel's authentication, and the route checks the ticket belongs to the authenticated user.

Under `driver=api` this is the only thing that can work: the file sits on the central application's disk, which the satellite has no credentials for, so `getUrl()` throws there on purpose. Under `driver=database` it serves the same file as before, with one check it did not have.

The Admin and Operator views are untouched and still link the disk directly.

### What a satellite is not offered

Each because the transport cannot do it honestly, not because it was awkward:

- **The Admin and Operator panels throw at registration.** They read operators, canned responses, history and other people's tickets, and act on assignment, internal notes and arbitrary status changes — none of which the API exposes. Failing at boot says so once, to a developer; an empty panel says it later, one page at a time, to whoever opened it.
- **The internal-note toggle is hidden.** `addNote()` throws over the API.
- **The department, category and assignee are left out** of the table and the detail view, not hidden — a hidden entry is still one whose state may be resolved, and reading any of those relations on an API-hydrated ticket throws.
- **The department and soft-delete filters are gone**, and `reference_number` and `title` are searchable but not sortable. `GET tickets` narrows by status and priority and sorts by `Ticket::SORTABLE`, which has neither.
- **Uploads are capped** at `help-desk.api.max_inline_attachment` (2 MB) rather than `ticket.max_file_size`. A file travels base64 encoded inside the signed body, so it grows by a third and is held in memory on both ends.

Closing and reopening your own ticket stay, on both drivers.

### Tests

A new `ApiDriver` suite boots a satellite: the User panel alone, `Http::fake()`, and **only the users table migrated**. The missing help desk tables are the point — a query that slips back into the panel fails there rather than quietly returning nothing.

Two of its twenty tests exist for failures that are silent by nature: a page of tickets must render as more than one row, and the attachment route must reject an unauthenticated request.

### Upgrading

```bash
composer update jeffersongoncalves/filament-help-desk



```
No migration. Applications already on `driver=database` need no configuration change; the only visible difference is that attachment links now point at the package route.

## 3.3.0 - 2026-09-16

Requires `jeffersongoncalves/laravel-help-desk` `^1.6`, which closes the cross-application identity work.

### laravel-help-desk 1.6

`TicketHistory` and `TicketWatcher` now carry the same identity snapshot `Ticket`, `TicketComment` and `TicketAttachment` already had, so all five models share one contract: prefer the live model, fall back to the copy, never instantiate a class this application does not have.

No panel code changed. Every history entry and watcher the panels create goes through `TicketService` with a `performer:` argument, so the base package writes those snapshots itself.

**Upgrading needs one migration** from the base package, on the application that owns the schema:

```bash
composer update jeffersongoncalves/laravel-help-desk
php artisan vendor:publish --tag=help-desk-migrations
php artisan migrate




```
`add_metadata_to_help_desk_ticket_watchers_table` adds a nullable JSON column to `help_desk_ticket_watchers`, the last of the five tables without one. Satellite applications do not run it — see the README.

### The changelog workflow no longer trusts the release tag

`.github/workflows/update-changelog.yml` interpolated `${{ github.event.release.tag_name }}` straight into a `run:` block. GitHub substitutes that before the shell parses the script, so a tag containing shell metacharacters became code running with a `contents: write` token (CWE-78). The tag now travels through `env:` and is read as a quoted variable.

The same workflow handed `release.target_commitish` to `git-auto-commit-action` as a branch name. A release can target a full commit SHA, which is not a branch; a new step verifies the target resolves to one and fails with a clear message otherwise.

Both are the fixes `laravel-help-desk` reviewed and shipped in its own v1.4.1. Workflow only — nothing in the published package changed.

### The Boost guidelines caught up

They had not moved since the package shipped, so an agent reading them would write exactly the code the last few releases fixed: `TextColumn::make('user.name')`, `get_class()` on a morph type, an eager loaded `author` relation. The guidelines now lead with those, the accessors that replace them, the uploader snapshot the panels write themselves, the current shared-concern signatures, and the satellite plus central topology.

They also move to `resources/boost/guidelines/core.blade.php`, the layout every other Filament plugin in this account uses.

### Upgrading

```bash
composer update jeffersongoncalves/filament-help-desk




```
Plus the base package migration above. No configuration change.

## 3.2.1 - 2026-09-16

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

## 3.2.0 - 2026-09-16

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
