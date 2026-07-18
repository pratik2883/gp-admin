<?php

namespace App\Filament\Widgets;

use App\Models\Referral;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReferralStats extends ChartWidget
{
    protected ?string $heading = 'Referral Statistics';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected function getData(): array
    {
        $from = Carbon::today()->subDays(29);
        $to = Carbon::today();

        $cacheKey = 'dashboard.referrals.chart.'.$from->toDateString().'.'.$to->toDateString();

        $rows = Cache::remember($cacheKey, 300, function () use ($from, $to) {
            return Referral::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
                DB::raw("SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted"),
                DB::raw("SUM(CASE WHEN status = 'consulted' THEN 1 ELSE 0 END) as consulted"),
                DB::raw("SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed")
            )
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });

        $labels = [];
        $sent = $accepted = $consulted = $closed = [];

        for ($d = 0; $d < 30; $d++) {
            $day = $from->copy()->addDays($d)->toDateString();
            $labels[] = $from->copy()->addDays($d)->format('d M');
            $row = $rows->firstWhere('date', $day);
            $sent[] = $row->sent ?? 0;
            $accepted[] = $row->accepted ?? 0;
            $consulted[] = $row->consulted ?? 0;
            $closed[] = $row->closed ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sent',
                    'data' => $sent,
                    'borderColor' => '#f59e0b',
                ],
                [
                    'label' => 'Accepted',
                    'data' => $accepted,
                    'borderColor' => '#3b82f6',
                ],
                [
                    'label' => 'Consulted',
                    'data' => $consulted,
                    'borderColor' => '#22c55e',
                ],
                [
                    'label' => 'Closed',
                    'data' => $closed,
                    'borderColor' => '#6b7280',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
