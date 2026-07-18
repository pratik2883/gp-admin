<?php

namespace App\Filament\Widgets;

use App\Models\DiagnosticCenter;
use App\Models\Gp;
use App\Models\Location;
use App\Models\Referral;
use App\Models\Specialist;
use App\Models\SupportTicket;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SummaryStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Dashboard Overview';

    protected function getStats(): array
    {
        $counts = Cache::remember('dashboard.summary.counts.v3', 60, function (): array {
            $from = Carbon::today()->subDays(6);

            $specialistTrend = Specialist::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', $from)
                ->groupBy('date')
                ->pluck('total', 'date');

            $gpTrend = Gp::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', $from)
                ->groupBy('date')
                ->pluck('total', 'date');

            $referralTrend = Referral::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', $from)
                ->groupBy('date')
                ->pluck('total', 'date');

            $subscriptionTrend = UserSubscription::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', $from)
                ->where('status', 'active')
                ->groupBy('date')
                ->pluck('total', 'date');

            $ticketTrend = SupportTicket::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', $from)
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
                'specialists' => Specialist::count(),
                'gps' => Gp::count(),
                'locations' => Location::count(),
                'referrals' => Referral::count(),
                'diagnostic_centers' => DiagnosticCenter::count(),
                'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
                'open_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
                'pending_payments' => UserSubscription::where('status', 'pending_payment')->count(),
                'specialist_trend' => $fillTrend($specialistTrend),
                'gp_trend' => $fillTrend($gpTrend),
                'referral_trend' => $fillTrend($referralTrend),
                'subscription_trend' => $fillTrend($subscriptionTrend),
                'ticket_trend' => $fillTrend($ticketTrend),
            ];
        });

        return [
            Stat::make('Specialists', $counts['specialists'])
                ->description('Registered specialists')
                ->descriptionIcon('heroicon-m-user-group')
                ->icon('heroicon-o-user-group')
                ->chart($counts['specialist_trend'])
                ->color('indigo')
                ->url(route('filament.admin.resources.specialists.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-indigo-500/10 shadow-md']),

            Stat::make('GP Signups', $counts['gps'])
                ->description('Registered general practitioners')
                ->descriptionIcon('heroicon-m-user')
                ->icon('heroicon-o-user')
                ->chart($counts['gp_trend'])
                ->color('emerald')
                ->url(route('filament.admin.resources.gps.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-emerald-500/10 shadow-md']),

            Stat::make('Locations', $counts['locations'])
                ->description('Active service locations')
                ->descriptionIcon('heroicon-m-map-pin')
                ->icon('heroicon-o-map-pin')
                ->color('amber')
                ->url(route('filament.admin.resources.locations.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-amber-500/10 shadow-md']),

            Stat::make('Referrals', $counts['referrals'])
                ->description('Total referrals processed')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-arrow-trending-up')
                ->chart($counts['referral_trend'])
                ->color('rose')
                ->url(route('filament.admin.resources.referrals.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-rose-500/10 shadow-md']),

            Stat::make('Active Subscriptions', $counts['active_subscriptions'])
                ->description('Paid & active plans')
                ->descriptionIcon('heroicon-m-credit-card')
                ->icon('heroicon-o-credit-card')
                ->chart($counts['subscription_trend'])
                ->color('violet')
                ->url(route('filament.admin.resources.user-subscriptions.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-violet-500/10 shadow-md']),

            Stat::make('Open Tickets', $counts['open_tickets'])
                ->description('Support tickets needing attention')
                ->descriptionIcon('heroicon-m-lifebuoy')
                ->icon('heroicon-o-lifebuoy')
                ->chart($counts['ticket_trend'])
                ->color('orange')
                ->url(route('filament.admin.resources.support-tickets.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-orange-500/10 shadow-md']),

            Stat::make('Pending Payments', $counts['pending_payments'])
                ->description('Awaiting payment confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-clock')
                ->color('yellow')
                ->url(route('filament.admin.resources.user-subscriptions.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-yellow-500/10 shadow-md']),

            Stat::make('Diagnostic Centers', $counts['diagnostic_centers'])
                ->description('Registered diagnostic centers')
                ->descriptionIcon('heroicon-m-building-library')
                ->icon('heroicon-o-building-library')
                ->color('cyan')
                ->url(route('filament.admin.resources.diagnostic-centers.index'), shouldOpenInNewTab: false)
                ->extraAttributes(['class' => 'ring-1 ring-cyan-500/10 shadow-md']),
        ];
    }}
