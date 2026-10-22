<?php

namespace App\Filament\Pages;

use App\Models\DiagnosticCenter;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Specialist;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Every doctor, centre and hospital registered in the field, attributed to the
 * MR who onboarded them. Admin can filter by MR and re-attribute a record when
 * the wrong officer was credited.
 */
class OnboardedDoctors extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Onboarded Doctors';

    protected static ?string $title = 'Doctors onboarded by MRs';

    protected string $view = 'filament.pages.onboarded-doctors';

    public ?string $mrFilter = null;

    public ?string $typeFilter = null;

    public string $search = '';

    /** @var array<string, int|string> */
    public array $reassign = [];

    public function getSubheading(): ?string
    {
        return 'Field registrations attributed to each MR representative.';
    }

    /** @return array<int, string> */
    public function mrOptions(): array
    {
        return User::query()
            ->where('role', 'mr')
            ->orderBy('name')
            ->limit(300)
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * One merged list across the four profile tables.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        $mr = $this->mrFilter ? (int) $this->mrFilter : null;
        $search = trim($this->search);
        $type = $this->typeFilter;

        $rows = collect();

        if (! $type || $type === 'gp') {
            $rows = $rows->merge(
                Gp::with('user')
                    ->whereNotNull('onboarded_by_mr_id')
                    ->when($mr, fn ($q, $v) => $q->where('onboarded_by_mr_id', $v))
                    ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")))
                    ->latest()
                    ->limit(200)
                    ->get()
                    ->map(fn (Gp $g): array => [
                        'key' => 'gp:'.$g->id,
                        'type' => 'GP',
                        'name' => $g->user?->name ?? $g->clinic_name,
                        'detail' => $g->clinic_name,
                        'city' => $g->city,
                        'mobile' => $g->user?->mobile,
                        'mr_id' => $g->onboarded_by_mr_id,
                        'mr_name' => User::whereKey($g->onboarded_by_mr_id)->value('name'),
                        'created_at' => $g->created_at,
                    ])
            );
        }

        if (! $type || $type === 'specialist') {
            $rows = $rows->merge(
                Specialist::with('user')
                    ->whereNotNull('onboarded_by_mr_id')
                    ->when($mr, fn ($q, $v) => $q->where('onboarded_by_mr_id', $v))
                    ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")))
                    ->latest()
                    ->limit(200)
                    ->get()
                    ->map(fn (Specialist $s): array => [
                        'key' => 'specialist:'.$s->id,
                        'type' => 'Specialist',
                        'name' => $s->user?->name,
                        'detail' => $s->hospital_name,
                        'city' => $s->clinic_city,
                        'mobile' => $s->user?->mobile,
                        'mr_id' => $s->onboarded_by_mr_id,
                        'mr_name' => User::whereKey($s->onboarded_by_mr_id)->value('name'),
                        'created_at' => $s->created_at,
                    ])
            );
        }

        if (! $type || $type === 'diagnostic_center') {
            $rows = $rows->merge(
                DiagnosticCenter::with('user')
                    ->whereNotNull('onboarded_by_mr_id')
                    ->when($mr, fn ($q, $v) => $q->where('onboarded_by_mr_id', $v))
                    ->when($search !== '', fn ($q) => $q->where(function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%");
                    }))
                    ->latest()
                    ->limit(200)
                    ->get()
                    ->map(fn (DiagnosticCenter $c): array => [
                        'key' => 'diagnostic_center:'.$c->id,
                        'type' => 'Diagnostic',
                        'name' => $c->name,
                        'detail' => $c->center_type,
                        'city' => $c->micro_area,
                        'mobile' => $c->mobile_number,
                        'mr_id' => $c->onboarded_by_mr_id,
                        'mr_name' => User::whereKey($c->onboarded_by_mr_id)->value('name'),
                        'created_at' => $c->created_at,
                    ])
            );
        }

        if (! $type || $type === 'hospital') {
            $rows = $rows->merge(
                Hospital::query()
                    ->whereNotNull('onboarded_by_mr_id')
                    ->when($mr, fn ($q, $v) => $q->where('onboarded_by_mr_id', $v))
                    ->when($search !== '', fn ($q) => $q->where(function ($h) use ($search) {
                        $h->where('name', 'like', "%{$search}%")
                            ->orWhere('contact_number', 'like', "%{$search}%");
                    }))
                    ->latest()
                    ->limit(200)
                    ->get()
                    ->map(fn (Hospital $h): array => [
                        'key' => 'hospital:'.$h->id,
                        'type' => 'Hospital',
                        'name' => $h->name,
                        'detail' => $h->hospital_type,
                        'city' => $h->city,
                        'mobile' => $h->contact_number,
                        'mr_id' => $h->onboarded_by_mr_id,
                        'mr_name' => User::whereKey($h->onboarded_by_mr_id)->value('name'),
                        'created_at' => $h->created_at,
                    ])
            );
        }

        return $rows->sortByDesc('created_at')->values();
    }

    public function saveReassign(string $key): void
    {
        [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
        $mrId = (int) ($this->reassign[$key] ?? 0);

        if (! $id || ! in_array($type, ['gp', 'specialist', 'diagnostic_center', 'hospital'], true)) {
            return;
        }

        if ($mrId <= 0 || ! User::where('role', 'mr')->whereKey($mrId)->exists()) {
            Notification::make()
                ->title('Pick an MR representative first')
                ->warning()
                ->send();

            return;
        }

        $model = match ($type) {
            'gp' => Gp::class,
            'specialist' => Specialist::class,
            'diagnostic_center' => DiagnosticCenter::class,
            'hospital' => Hospital::class,
        };

        $model::whereKey((int) $id)->update(['onboarded_by_mr_id' => $mrId]);

        unset($this->reassign[$key]);

        Notification::make()
            ->title('Onboarding re-assigned to '.User::whereKey($mrId)->value('name'))
            ->success()
            ->send();
    }
}
