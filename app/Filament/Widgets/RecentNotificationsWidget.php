<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class RecentNotificationsWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-notifications-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getNotificationsProperty()
    {
        return auth()->user()
            ? auth()->user()->notifications()
                ->latest()
                ->limit(5)
                ->get()
            : collect();
    }
}
