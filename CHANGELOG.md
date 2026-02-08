# Changelog

All notable changes to `filament-help-desk` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.0] - 2026-XX-XX

### Changed
- **BREAKING:** Requires Laravel ^11.28, Filament ^5.0 (Livewire v4)
- Updated `orchestra/testbench` to `^10.0|^11.0`

## [2.0.0] - 2026-XX-XX

### Changed
- **BREAKING:** Requires PHP ^8.2, Laravel ^11.0, Filament ^4.0
- Updated `Form`/`Infolist` method signatures to use `Schema` parameter
- Moved `Forms\Get`/`Forms\Set` to `Schemas\Components\Utilities` namespace
- Moved `Infolists\Components\Section` to `Schemas\Components\Section` namespace
- Renamed table `->actions()` to `->recordActions()` and `->bulkActions()` to `->toolbarActions()`
- Moved table action imports from `Filament\Tables\Actions` to `Filament\Actions` namespace
- Converted heroicon strings to `Filament\Support\Icons\Heroicon` enum
- Removed `static` keyword from `$view` property in ViewTicket pages
- Replaced Tailwind utility classes in Blade views with custom `fi-hd-*` CSS classes for Tailwind v4 compatibility
- Simplified TestCase service providers for Filament v4

### Added
- GitHub Actions CI workflow for automated testing across PHP 8.2/8.3/8.4
- Custom CSS with Filament CSS variables for dark mode support

### Removed
- Tailwind CSS, autoprefixer, and @tailwindcss/* build dependencies
- tailwind.config.js (no longer needed)

## [1.0.0] - 2025-XX-XX

### Added
- Initial release
- **FilamentHelpDeskUserPlugin** - End-user ticket submission and tracking
  - Ticket creation with department, category, priority, and file attachments
  - Ticket listing with status and priority filters
  - Ticket detail view with comment timeline and reply form
  - User ticket stats widget (open, pending, resolved, total)
- **FilamentHelpDeskOperatorPlugin** - Support agent ticket management
  - Tabbed ticket queue (My Tickets, Unassigned, All)
  - Ticket management with status changes, assignment, and canned responses
  - Internal notes support
  - Tickets by status chart widget
  - Assigned tickets table widget
- **FilamentHelpDeskAdminPlugin** - System administration
  - Department CRUD with operator management
  - Category CRUD with hierarchical support
  - Canned response CRUD
  - Email channel CRUD
  - Full ticket management with all status tabs
  - Stats overview and priority distribution widgets
- Shared concerns (HasTicketForm, HasTicketTable, HasTicketInfolist, InteractsWithTicketComments)
- Blade views for comment component and timeline
- Translations: English (en) and Brazilian Portuguese (pt_BR)
- Configuration file for customizing resources, navigation, and icons
- Laravel Boost guidelines and development skill
