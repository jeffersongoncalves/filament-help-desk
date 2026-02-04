<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class TicketsByPriorityWidget extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return __('filament-help-desk::filament-help-desk.widgets.tickets_by_priority.heading');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = [];
        $labels = [];
        $colors = [];

        $colorMap = [
            TicketPriority::Low->value => 'rgb(156, 163, 175)',     // gray
            TicketPriority::Medium->value => 'rgb(59, 130, 246)',    // blue
            TicketPriority::High->value => 'rgb(249, 115, 22)',      // orange
            TicketPriority::Urgent->value => 'rgb(239, 68, 68)',     // red
        ];

        foreach (TicketPriority::cases() as $priority) {
            $labels[] = $priority->label();
            $counts[] = Ticket::query()
                ->byPriority($priority)
                ->count();
            $colors[] = $colorMap[$priority->value] ?? 'rgb(156, 163, 175)';
        }

        return [
            'datasets' => [
                [
                    'label' => __('filament-help-desk::filament-help-desk.widgets.tickets_by_priority.dataset_label'),
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
