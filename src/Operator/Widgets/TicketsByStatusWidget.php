<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets;

use Filament\Widgets\ChartWidget;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class TicketsByStatusWidget extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('filament-help-desk::filament-help-desk.widgets.tickets_by_status');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $statusCounts = Ticket::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        $colorMap = [
            TicketStatus::Open->value => '#3b82f6',       // blue / info
            TicketStatus::Pending->value => '#f59e0b',     // amber / warning
            TicketStatus::InProgress->value => '#6366f1',  // indigo / primary
            TicketStatus::OnHold->value => '#6b7280',      // gray
            TicketStatus::Resolved->value => '#10b981',    // emerald / success
            TicketStatus::Closed->value => '#ef4444',      // red / danger
        ];

        foreach (TicketStatus::cases() as $status) {
            $count = $statusCounts->get($status->value, 0);

            $labels[] = $status->label();
            $data[] = $count;
            $colors[] = $colorMap[$status->value] ?? '#9ca3af';
        }

        return [
            'datasets' => [
                [
                    'label' => __('filament-help-desk::filament-help-desk.widgets.tickets_by_status'),
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
