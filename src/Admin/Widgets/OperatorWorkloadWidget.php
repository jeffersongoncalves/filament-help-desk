<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class OperatorWorkloadWidget extends ChartWidget
{
    protected ?string $heading = null;

    protected static ?int $sort = 5;

    public function getHeading(): ?string
    {
        return __('filament-help-desk::filament-help-desk.widgets.operator_workload.heading');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $operatorModel = config('help-desk.models.operator');
        $operatorMorphClass = (new $operatorModel)->getMorphClass();

        // One grouped query (assigned_to_id x status) instead of 2 counts per operator.
        // toBase() drops Eloquent's enum cast on `status`, so it stays the raw string
        // the column stores and can be compared directly against TicketStatus::value.
        $counts = Ticket::query()
            ->toBase()
            ->selectRaw('assigned_to_id, status, count(*) as aggregate')
            ->where('assigned_to_type', $operatorMorphClass)
            ->whereIn('status', [TicketStatus::Pending->value, TicketStatus::Resolved->value])
            ->groupBy('assigned_to_id', 'status')
            ->get();

        $operatorIds = $counts->pluck('assigned_to_id')->unique()->filter();

        $operatorNames = $operatorModel::query()
            ->whereIn('id', $operatorIds)
            ->pluck('name', 'id');

        // Keyed lookup instead of scanning $counts per operator per status.
        $byOperatorAndStatus = [];

        foreach ($counts as $row) {
            $byOperatorAndStatus[$row->assigned_to_id.':'.$row->status] = (int) $row->aggregate;
        }

        $pending = [];
        $resolved = [];
        $labels = [];

        foreach ($operatorIds as $operatorId) {
            $labels[] = $operatorNames[$operatorId] ?? (string) $operatorId;
            $pending[] = $byOperatorAndStatus[$operatorId.':'.TicketStatus::Pending->value] ?? 0;
            $resolved[] = $byOperatorAndStatus[$operatorId.':'.TicketStatus::Resolved->value] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => __('filament-help-desk::filament-help-desk.widgets.operator_workload.pending_label'),
                    'data' => $pending,
                    'backgroundColor' => 'rgb(249, 115, 22)',
                ],
                [
                    'label' => __('filament-help-desk::filament-help-desk.widgets.operator_workload.resolved_label'),
                    'data' => $resolved,
                    'backgroundColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
