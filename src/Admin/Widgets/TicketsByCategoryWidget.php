<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use JeffersonGoncalves\HelpDesk\Models\Category;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class TicketsByCategoryWidget extends ChartWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 4;

    public function getHeading(): ?string
    {
        return __('filament-help-desk::filament-help-desk.widgets.tickets_by_category.heading');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // One grouped query instead of one count() per category.
        $counts = Ticket::query()
            ->selectRaw('category_id, count(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $categoryNames = Category::query()
            ->whereIn('id', $counts->keys()->filter())
            ->pluck('name', 'id');

        $labels = [];
        $data = [];

        foreach ($counts as $categoryId => $count) {
            $labels[] = $categoryId
                ? ($categoryNames[$categoryId] ?? __('filament-help-desk::filament-help-desk.placeholders.na'))
                : __('filament-help-desk::filament-help-desk.placeholders.no_category');
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => __('filament-help-desk::filament-help-desk.widgets.tickets_by_category.dataset_label'),
                    'data' => $data,
                    'backgroundColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
