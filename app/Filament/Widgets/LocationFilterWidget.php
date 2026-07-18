<?php

namespace App\Filament\Widgets;

use App\Models\Hospital;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class LocationFilterWidget extends Widget
{
    protected string $view = 'filament.widgets.location-filter-widget';

    protected int|string|array $columnSpan = 'full';

    public ?string $city = null;

    public function mount(): void
    {
        $this->city = session('dashboard_city');
    }

    protected function getViewData(): array
    {
        $cities = Cache::remember('dashboard.locations.cities', 600, function (): array {
            return Hospital::query()
                ->whereNotNull('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city')
                ->filter()
                ->values()
                ->toArray();
        });

        return [
            'cities' => $cities,
        ];
    }

    public function apply(): void
    {
        session(['dashboard_city' => $this->city ?: null]);
        $this->redirect('/admin');
    }

    public function resetFilter(): void
    {
        $this->city = null;
        session()->forget('dashboard_city');
        $this->redirect('/admin');
    }
}
