<?php

namespace App\Filament\Widgets;

use App\Models\UserSubscription;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ActiveSubscriptionsWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected function getStats(): array
    {
        $counts = Cache::remember('dashboard.subscriptions.breakdown.v2', 60, function (): array {
            $from = Carbon::today()->subDays(6);

            $activeTrend = UserSubscription::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', $from)
                ->where('status', 'active')
                ->groupBy('date')
                ->pluck('total', 'date');

            $fillTrend = function ($trend) use ($from): array {
                $data = [];
                for ($i = 0; $i < 7; $i++) {
                    $day = $from->copy()->addDays($i)->toDateString();
                    $data[] = (int) ($trend[$day] ?? 0);
                }
                return $data;
            };

            return [
                'active' => UserSubscription::where('status', 'active')->count(),
                'expired' => UserSubscription::where('status', 'expired')->count(),
                'pending_payment' => UserSubscription::where('status', 'pending_payment')->count(),
                'pending_activation' => UserSubscription::where('status', 'pending_activation')->count(),
                'cancelled' => UserSubscription::where('status', 'cancelled')->count(),
                'active_trend' => $fillTrend($activeTrend),
            ];
        });

        return [
            Stat::make('Active', $counts['active'])
                ->description('Active subscription plans')
                ->descriptionIcon('heroicon-m-check-badge')
                ->icon('heroicon-o-check-badge')
                ->chart($counts['active_trend'])
                ->color('emerald')
                ->extraAttributes(['class' => 'ring-1 ring-emerald-500/10 shadow-md']),

            Stat::make('Pending Payment', $counts['pending_payment'])
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-banknotes')
                ->icon('heroicon-o-banknotes')
                ->color('amber')
                ->extraAttributes(['class' => 'ring-1 ring-amber-500/10 shadow-md']),

            Stat::make('Pending Activation', $counts['pending_activation'])
                ->description('Awaiting manual activation')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('blue')
                ->extraAttributes(['class' => 'ring-1 ring-blue-500/10 shadow-md']),

            Stat::make('Expired', $counts['expired'])
                ->description('Expired subscriptions')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->extraAttributes(['class' => 'ring-1 ring-gray-500/10 shadow-md']),

            Stat::make('Cancelled', $counts['cancelled'])
                ->description('Cancelled subscriptions')
                ->descriptionIcon('heroicon-m-x-circle')
                ->icon('heroicon-o-x-circle')
                ->color('rose')
                ->extraAttributes(['class' => 'ring-1 ring-rose-500/10 shadow-md']),
        ];
    }
}
