<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class TicketStatsOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $fourteenDaysAgo = Carbon::now()->subDays(14);

        // Total tickets
        $totalTickets = Ticket::query()->count();
        $totalLastWeek = Ticket::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();
        $totalPreviousWeek = Ticket::query()
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();

        // Open tickets
        $openTickets = Ticket::query()
            ->byStatus(TicketStatus::Open)
            ->count();
        $openLastWeek = Ticket::query()
            ->byStatus(TicketStatus::Open)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();
        $openPreviousWeek = Ticket::query()
            ->byStatus(TicketStatus::Open)
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();

        // Unassigned tickets
        $unassignedTickets = Ticket::query()
            ->unassigned()
            ->count();
        $unassignedLastWeek = Ticket::query()
            ->unassigned()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();
        $unassignedPreviousWeek = Ticket::query()
            ->unassigned()
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();

        // Overdue tickets (due_at is past and not closed/resolved)
        $overdueTickets = Ticket::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<', Carbon::now())
            ->whereNotIn('status', [
                TicketStatus::Closed->value,
                TicketStatus::Resolved->value,
            ])
            ->count();
        $overdueLastWeek = Ticket::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<', Carbon::now())
            ->where('due_at', '>=', $sevenDaysAgo)
            ->whereNotIn('status', [
                TicketStatus::Closed->value,
                TicketStatus::Resolved->value,
            ])
            ->count();
        $overduePreviousWeek = Ticket::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<', Carbon::now())
            ->where('due_at', '>=', $fourteenDaysAgo)
            ->where('due_at', '<', $sevenDaysAgo)
            ->whereNotIn('status', [
                TicketStatus::Closed->value,
                TicketStatus::Resolved->value,
            ])
            ->count();

        return [
            $this->buildStat(
                label: __('filament-help-desk::filament-help-desk.widgets.admin_stats.total_tickets'),
                value: $totalTickets,
                current: $totalLastWeek,
                previous: $totalPreviousWeek,
                icon: 'heroicon-m-hashtag',
                color: 'gray',
            ),

            $this->buildStat(
                label: __('filament-help-desk::filament-help-desk.widgets.admin_stats.open_tickets'),
                value: $openTickets,
                current: $openLastWeek,
                previous: $openPreviousWeek,
                icon: 'heroicon-m-inbox',
                color: 'warning',
            ),

            $this->buildStat(
                label: __('filament-help-desk::filament-help-desk.widgets.admin_stats.unassigned_tickets'),
                value: $unassignedTickets,
                current: $unassignedLastWeek,
                previous: $unassignedPreviousWeek,
                icon: 'heroicon-m-user-minus',
                color: 'info',
            ),

            $this->buildStat(
                label: __('filament-help-desk::filament-help-desk.widgets.admin_stats.overdue_tickets'),
                value: $overdueTickets,
                current: $overdueLastWeek,
                previous: $overduePreviousWeek,
                icon: 'heroicon-m-exclamation-triangle',
                color: 'danger',
            ),
        ];
    }

    private function buildStat(
        string $label,
        int $value,
        int $current,
        int $previous,
        string $icon,
        string $color,
    ): Stat {
        $diff = $current - $previous;
        $trend = $diff > 0 ? '+' . $diff : (string) $diff;

        $description = __('filament-help-desk::filament-help-desk.widgets.admin_stats.trend_description', [
            'trend' => $trend,
            'period' => __('filament-help-desk::filament-help-desk.widgets.admin_stats.last_7_days'),
        ]);

        $stat = Stat::make($label, $value)
            ->description($description)
            ->descriptionIcon($icon)
            ->color($color);

        if ($diff > 0) {
            $stat->descriptionIcon('heroicon-m-arrow-trending-up');
        } elseif ($diff < 0) {
            $stat->descriptionIcon('heroicon-m-arrow-trending-down');
        }

        return $stat;
    }
}
