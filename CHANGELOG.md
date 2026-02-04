# Changelog

All notable changes to `filament-help-desk` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
