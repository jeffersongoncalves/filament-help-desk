<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Pages;

use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use JeffersonGoncalves\Filament\Kanban\Pages\KanbanBoard;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;
use JeffersonGoncalves\HelpDesk\Services\TicketService;
use Livewire\Attributes\On;

/**
 * Optional drag-and-drop alternative to the ticket table — see
 * FilamentHelpDeskOperatorPlugin::kanban(). Only ever registered when
 * jeffersongoncalves/filament-kanban is installed; this class itself is
 * never autoloaded otherwise (see the plugin's class_exists() guard).
 */
class TicketsKanbanBoard extends KanbanBoard
{
    protected static string $model = Ticket::class;

    protected static string $statusEnum = TicketStatus::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'status';

    // help_desk_tickets has no order_column (no migration for it — see
    // issue #58) — reads order by created_at instead, and both hooks below
    // skip persistOrder() entirely, so this column is never written to.
    protected static string $orderColumn = 'created_at';

    protected function statuses(): Collection
    {
        // Closed is deliberately left off the board: a Kanban is a working
        // queue, and a closed ticket is done being worked. Reopening it back
        // onto the board still goes through the ViewTicket action, not a drag.
        return collect([
            TicketStatus::Open,
            TicketStatus::Pending,
            TicketStatus::InProgress,
            TicketStatus::OnHold,
            TicketStatus::Resolved,
        ])->map(fn (TicketStatus $status): array => [
            'id' => $status->value,
            'label' => $status->label(),
        ]);
    }

    #[On('status-changed')]
    public function onStatusChanged(int|string $recordId, string $toStatus, array $toOrderedIds): void
    {
        /** @var Ticket|null $record */
        $record = $this->getEloquentQuery()->find($recordId);

        if (! $record) {
            return;
        }

        $fromStatus = $record->status;
        $targetStatus = TicketStatus::from($toStatus);

        if (! $this->canTransition($fromStatus, $targetStatus)) {
            $this->rejectMove($recordId, $fromStatus, $targetStatus);

            return;
        }

        // Routed through the service, not $record->update(): changeStatus()
        // is what fires TicketStatusChanged (SLA pause tracking listens on
        // it) and writes the history entry. A drag is a status change like
        // any other and must not skip either.
        app(TicketService::class)->changeStatus(
            ticket: $record,
            newStatus: $targetStatus,
            performer: Filament::auth()->user(),
        );
    }

    #[On('sort-changed')]
    public function onSortChanged(array $orderedIds): void
    {
        // No order_column to persist to — a pure in-column reorder is
        // visual only and reverts to created_at order on the next render.
    }
}
