<?php

namespace App\Filament\Pages;

use App\Models\DiagnosticCenter;
use App\Models\Gp;
use App\Models\Hospital;
use App\Models\Mr;
use App\Models\MrAssignment;
use App\Models\MrAttendance;
use App\Models\MrLeave;
use App\Models\MrVisit;
use App\Models\Specialist;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * One place for everything the field force does: today's feet-on-street,
 * visits, targets, onboarding, leave and open assignments — per MR, with a
 * drill-down for a single officer.
 */
class MrActivity extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static \UnitEnum|string|null $navigationGroup = 'MR Activity';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'MR Activity';

    protected static ?string $title = 'MR Activity';

    protected string $view = 'filament.pages.mr-activity';

    /** Selected MR (user id) for the drill-down view. */
    #[Url(as: 'mr')]
    public ?int $mr = null;

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->to = Carbon::today()->toDateString();
    }

    public function getSubheading(): ?string
    {
        return 'Field activity, onboarding and assignments for every MR representative.';
    }

    private function rangeStart(): Carbon
    {
        return Carbon::parse($this->from ?: Carbon::now()->startOfMonth()->toDateString())->startOfDay();
    }

    private function rangeEnd(): Carbon
    {
        return Carbon::parse($this->to ?: Carbon::today()->toDateString())->endOfDay();
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $from = $this->rangeStart();
        $to = $this->rangeEnd();

        $mrUsers = Mr::where('status', 'active')->pluck('user_id');

        $visitsRange = MrVisit::whereBetween('visit_date', [$from->toDateString(), $to->toDateString()])->count();
        $visitsToday = MrVisit::whereDate('visit_date', Carbon::today())->count();
        $checkedInToday = MrAttendance::whereDate('date', Carbon::today())->whereNotNull('check_in_at')->count();

        $workDays = 0;
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            if ($day->isWeekday()) {
                $workDays++;
            }
        }

        $dailyTarget = (int) (Mr::where('status', 'active')->avg('daily_visit_target') ?: 1);
        $expected = max(1, $workDays * $dailyTarget);
        $achievement = (int) round(($visitsRange / $expected) * 100);

        // A field-onboarded hospital creates both a Specialist row and a
        // `hospitals` row, so specialists with a hospital account are excluded
        // here to avoid counting the same hospital twice.
        $specialistCount = Specialist::whereNotNull('onboarded_by_mr_id')
            ->whereBetween('created_at', [$from, $to])
            ->whereDoesntHave('user', fn ($q) => $q->where('role_subtype', 'hospital'))
            ->count();

        $onboardedRange = Gp::whereNotNull('onboarded_by_mr_id')->whereBetween('created_at', [$from, $to])->count()
            + $specialistCount
            + DiagnosticCenter::whereNotNull('onboarded_by_mr_id')->whereBetween('created_at', [$from, $to])->count()
            + Hospital::whereNotNull('onboarded_by_mr_id')->whereBetween('created_at', [$from, $to])->count();

        return [
            'active_mrs' => $mrUsers->count(),
            'checked_in_today' => $checkedInToday,
            'visits_today' => $visitsToday,
            'visits_range' => $visitsRange,
            'achievement' => $achievement,
            'expected_visits' => $expected,
            'pending_leaves' => MrLeave::where('status', 'pending')->count(),
            'onboarded_range' => $onboardedRange,
            'open_assignments' => MrAssignment::whereIn('status', ['pending', 'in_progress'])->count(),
        ];
    }

    /**
     * Per-MR rollup for the selected range.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        $from = $this->rangeStart()->toDateString();
        $to = $this->rangeEnd()->toDateString();

        $mrs = Mr::with('user')->where('status', 'active')->orderBy('employee_code')->get();

        $visitsRange = MrVisit::whereBetween('visit_date', [$from, $to])
            ->selectRaw('mr_user_id, COUNT(*) as total')
            ->groupBy('mr_user_id')
            ->pluck('total', 'mr_user_id');

        $visitsToday = MrVisit::whereDate('visit_date', Carbon::today())
            ->selectRaw('mr_user_id, COUNT(*) as total')
            ->groupBy('mr_user_id')
            ->pluck('total', 'mr_user_id');

        $attendance = MrAttendance::whereDate('date', Carbon::today())->get()->keyBy('mr_user_id');

        $leaves = MrLeave::whereIn('status', ['pending'])
            ->selectRaw('mr_user_id, COUNT(*) as total')
            ->groupBy('mr_user_id')
            ->pluck('total', 'mr_user_id');

        $assignments = MrAssignment::whereIn('status', ['pending', 'in_progress'])
            ->selectRaw('mr_user_id, COUNT(*) as total')
            ->groupBy('mr_user_id')
            ->pluck('total', 'mr_user_id');

        $gps = Gp::whereNotNull('onboarded_by_mr_id')
            ->selectRaw('onboarded_by_mr_id as mr_id, COUNT(*) as total')
            ->groupBy('onboarded_by_mr_id')->pluck('total', 'mr_id');
        $specialists = Specialist::whereNotNull('onboarded_by_mr_id')
            ->selectRaw('onboarded_by_mr_id as mr_id, COUNT(*) as total')
            ->groupBy('onboarded_by_mr_id')->pluck('total', 'mr_id');
        $centers = DiagnosticCenter::whereNotNull('onboarded_by_mr_id')
            ->selectRaw('onboarded_by_mr_id as mr_id, COUNT(*) as total')
            ->groupBy('onboarded_by_mr_id')->pluck('total', 'mr_id');
        $hospitals = Hospital::whereNotNull('onboarded_by_mr_id')
            ->selectRaw('onboarded_by_mr_id as mr_id, COUNT(*) as total')
            ->groupBy('onboarded_by_mr_id')->pluck('total', 'mr_id');

        $lastVisits = MrVisit::selectRaw('mr_user_id, MAX(visit_date) as last_visit')
            ->groupBy('mr_user_id')
            ->pluck('last_visit', 'mr_user_id');

        return $mrs->map(function (Mr $mr) use ($visitsRange, $visitsToday, $attendance, $leaves, $assignments, $gps, $specialists, $centers, $hospitals, $lastVisits): array {
            $today = $attendance->get($mr->user_id);
            $onboarded = (int) ($gps[$mr->user_id] ?? 0)
                + (int) ($specialists[$mr->user_id] ?? 0)
                + (int) ($centers[$mr->user_id] ?? 0)
                + (int) ($hospitals[$mr->user_id] ?? 0);

            return [
                'user_id' => $mr->user_id,
                'name' => $mr->user?->name ?? '—',
                'employee_code' => $mr->employee_code,
                'territory' => $mr->territory_zone,
                'status' => $today
                    ? ($today->check_out_at ? 'Checked out' : ($today->check_in_at ? 'Checked in' : 'Not marked'))
                    : 'Not marked',
                'check_in' => $today?->check_in_at?->format('h:i A'),
                'visits_today' => (int) ($visitsToday[$mr->user_id] ?? 0),
                'daily_target' => (int) $mr->daily_visit_target,
                'visits_range' => (int) ($visitsRange[$mr->user_id] ?? 0),
                'onboarded' => $onboarded,
                'pending_leaves' => (int) ($leaves[$mr->user_id] ?? 0),
                'open_assignments' => (int) ($assignments[$mr->user_id] ?? 0),
                'last_visit' => $lastVisits[$mr->user_id] ?? null,
            ];
        });
    }

    /**
     * Everything about one MR, for the drill-down.
     *
     * @return array<string, mixed>|null
     */
    public function detail(): ?array
    {
        if (! $this->mr) {
            return null;
        }

        $mr = Mr::with('user')->where('user_id', $this->mr)->first();

        if (! $mr) {
            return null;
        }

        $from = $this->rangeStart();
        $to = $this->rangeEnd();

        return [
            'record' => $mr,
            'user' => $mr->user,
            'visits' => MrVisit::with(['hospital', 'doctorUser'])
                ->where('mr_user_id', $mr->user_id)
                ->orderByDesc('visit_date')
                ->limit(15)
                ->get(),
            'visits_count' => MrVisit::where('mr_user_id', $mr->user_id)
                ->whereBetween('visit_date', [$from->toDateString(), $to->toDateString()])
                ->count(),
            'attendance_count' => MrAttendance::where('mr_user_id', $mr->user_id)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->whereNotNull('check_in_at')
                ->count(),
            'today_attendance' => MrAttendance::where('mr_user_id', $mr->user_id)
                ->whereDate('date', Carbon::today())
                ->first(),
            'leaves' => MrLeave::where('mr_user_id', $mr->user_id)->orderByDesc('start_date')->limit(10)->get(),
            'assignments' => MrAssignment::with('assignable')
                ->where('mr_user_id', $mr->user_id)
                ->orderByDesc('created_at')
                ->limit(15)
                ->get(),
            'onboarded' => collect()
                ->merge(Gp::with('user')->where('onboarded_by_mr_id', $mr->user_id)->latest()->limit(10)->get()->map(fn (Gp $g) => [
                    'type' => 'GP',
                    'name' => $g->user?->name ?? $g->clinic_name,
                    'detail' => $g->clinic_name,
                    'mobile' => $g->user?->mobile,
                    'created_at' => $g->created_at,
                ]))
                ->merge(Specialist::with('user')->where('onboarded_by_mr_id', $mr->user_id)->latest()->limit(10)->get()->map(fn (Specialist $s) => [
                    'type' => 'Specialist',
                    'name' => $s->user?->name,
                    'detail' => $s->hospital_name,
                    'mobile' => $s->user?->mobile,
                    'created_at' => $s->created_at,
                ]))
                ->merge(DiagnosticCenter::where('onboarded_by_mr_id', $mr->user_id)->latest()->limit(10)->get()->map(fn (DiagnosticCenter $c) => [
                    'type' => 'Diagnostic',
                    'name' => $c->name,
                    'detail' => $c->center_type,
                    'mobile' => $c->mobile_number,
                    'created_at' => $c->created_at,
                ]))
                ->merge(Hospital::where('onboarded_by_mr_id', $mr->user_id)->latest()->limit(10)->get()->map(fn (Hospital $h) => [
                    'type' => 'Hospital',
                    'name' => $h->name,
                    'detail' => $h->hospital_type,
                    'mobile' => $h->contact_number,
                    'created_at' => $h->created_at,
                ]))
                ->sortByDesc('created_at')
                ->values(),
        ];
    }
}
