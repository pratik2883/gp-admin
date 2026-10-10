<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): array|int
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\LocationFilterWidget::class,
            \App\Filament\Widgets\SummaryStats::class,
            \App\Filament\Widgets\ReferralStats::class,
            \App\Filament\Widgets\SpecialistsTableWidget::class,
            \App\Filament\Widgets\LocationsTableWidget::class,
            \App\Filament\Widgets\GpSignupsWidget::class,
            \App\Filament\Widgets\ActiveSubscriptionsWidget::class,
            // The "Recent notifications" card was removed: Filament's header bell
            // lists the same notifications, so the duplicate list at the bottom of
            // the dashboard added nothing.
        ];
    }
}
