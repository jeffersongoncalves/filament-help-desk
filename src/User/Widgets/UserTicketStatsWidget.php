<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\User\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class UserTicketStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();

        $baseQuery = Ticket::query()
            ->where('user_type', get_class($user))
            ->where('user_id', $user->getAuthIdentifier());

        $openCount = (clone $baseQuery)
            ->byStatus(TicketStatus::Open)
            ->count();

        $pendingCount = (clone $baseQuery)
            ->whereIn('status', [
                TicketStatus::Pending,
                TicketStatus::InProgress,
                TicketStatus::OnHold,
            ])
            ->count();

        $resolvedCount = (clone $baseQuery)
            ->byStatus(TicketStatus::Resolved)
            ->count();

        $totalCount = (clone $baseQuery)->count();

        return [
            Stat::make(
                __('filament-help-desk::filament-help-desk.widgets.user_stats.open_tickets'),
                $openCount,
            )
                ->description(__('filament-help-desk::filament-help-desk.widgets.user_stats.open_tickets_description'))
                ->descriptionIcon('heroicon-m-inbox')
                ->color('warning'),

            Stat::make(
                __('filament-help-desk::filament-help-desk.widgets.user_stats.pending_tickets'),
                $pendingCount,
            )
                ->description(__('filament-help-desk::filament-help-desk.widgets.user_stats.pending_tickets_description'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make(
                __('filament-help-desk::filament-help-desk.widgets.user_stats.resolved_tickets'),
                $resolvedCount,
            )
                ->description(__('filament-help-desk::filament-help-desk.widgets.user_stats.resolved_tickets_description'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(
                __('filament-help-desk::filament-help-desk.widgets.user_stats.total_tickets'),
                $totalCount,
            )
                ->description(__('filament-help-desk::filament-help-desk.widgets.user_stats.total_tickets_description'))
                ->descriptionIcon('heroicon-m-hashtag')
                ->color('gray'),
        ];
    }
}
