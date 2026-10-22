<x-filament::page>
    @php
        $detail = $this->detail();
    @endphp

    @if ($detail)
        @php
            $record = $detail['record'];
            $user = $detail['user'];
            $today = $detail['today_attendance'];
        @endphp

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">{{ $user?->name ?? 'MR' }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ $record->employee_code ?? '—' }} ·
                        {{ $record->territory_zone ?? '—' }} ·
                        HQ {{ $record->headquarters_city ?? '—' }}
                    </p>
                </div>
                <x-filament::button color="gray" wire:click="$set('mr', null)" icon="heroicon-m-arrow-left">
                    All MRs
                </x-filament::button>
            </div>

            {{-- Today + range stats --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <x-filament::card>
                    <div class="text-xs text-gray-500">Today</div>
                    <div class="text-lg font-bold">
                        {{ $today?->check_in_at ? 'Checked in' : 'Not marked' }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $today?->check_in_at?->format('h:i A') ?? '' }}
                        {{ $today?->check_out_at ? '→ '.$today->check_out_at->format('h:i A') : '' }}
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Visits in range</div>
                    <div class="text-lg font-bold">{{ $detail['visits_count'] }}</div>
                    <div class="text-xs text-gray-500">Daily target {{ $record->daily_visit_target }}</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Days attended</div>
                    <div class="text-lg font-bold">{{ $detail['attendance_count'] }}</div>
                    <div class="text-xs text-gray-500">In selected range</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Open assignments</div>
                    <div class="text-lg font-bold">
                        {{ $detail['assignments']->whereIn('status', ['pending', 'in_progress'])->count() }}
                    </div>
                    <div class="text-xs text-gray-500">Assigned by admin</div>
                </x-filament::card>
            </div>

            {{-- Recent visits --}}
            <x-filament::section heading="Recent visits">
                @if ($detail['visits']->isEmpty())
                    <p class="text-sm text-gray-500">No visits logged yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Date</th>
                                <th class="py-2">Type</th>
                                <th class="py-2">Doctor / Hospital</th>
                                <th class="py-2">Purpose</th>
                                <th class="py-2">Follow-up</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detail['visits'] as $visit)
                                <tr class="border-t">
                                    <td class="py-2">{{ $visit->visit_date?->format('d M Y') }}</td>
                                    <td class="py-2">
                                        <x-filament::badge>{{ str_replace('_', ' ', $visit->doctor_type) }}</x-filament::badge>
                                    </td>
                                    <td class="py-2">{{ $visit->targetName() }}</td>
                                    <td class="py-2">{{ $visit->visit_purpose }}</td>
                                    <td class="py-2">{{ $visit->follow_up_date?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>

            {{-- Assignments --}}
            <x-filament::section heading="Assignments">
                @if ($detail['assignments']->isEmpty())
                    <p class="text-sm text-gray-500">Nothing assigned to this MR.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Assignment</th>
                                <th class="py-2">Target</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detail['assignments'] as $assignment)
                                <tr class="border-t">
                                    <td class="py-2">{{ $assignment->title }}</td>
                                    <td class="py-2">{{ $assignment->targetName() }}</td>
                                    <td class="py-2">
                                        <x-filament::badge>{{ str_replace('_', ' ', $assignment->status) }}</x-filament::badge>
                                    </td>
                                    <td class="py-2">{{ $assignment->due_date?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>

            {{-- Onboarded --}}
            <x-filament::section heading="Doctors onboarded">
                @if ($detail['onboarded']->isEmpty())
                    <p class="text-sm text-gray-500">No doctor onboarded by this MR.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Type</th>
                                <th class="py-2">Name</th>
                                <th class="py-2">Details</th>
                                <th class="py-2">Mobile</th>
                                <th class="py-2">Onboarded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detail['onboarded'] as $item)
                                <tr class="border-t">
                                    <td class="py-2">
                                        <x-filament::badge>{{ $item['type'] }}</x-filament::badge>
                                    </td>
                                    <td class="py-2">{{ $item['name'] ?? '—' }}</td>
                                    <td class="py-2">{{ $item['detail'] ?? '—' }}</td>
                                    <td class="py-2">{{ $item['mobile'] ?? '—' }}</td>
                                    <td class="py-2">{{ $item['created_at']?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>

            {{-- Leaves --}}
            <x-filament::section heading="Leave history">
                @if ($detail['leaves']->isEmpty())
                    <p class="text-sm text-gray-500">No leave applied.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">From</th>
                                <th class="py-2">To</th>
                                <th class="py-2">Reason</th>
                                <th class="py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detail['leaves'] as $leave)
                                <tr class="border-t">
                                    <td class="py-2">{{ $leave->start_date?->format('d M Y') }}</td>
                                    <td class="py-2">{{ $leave->end_date?->format('d M Y') }}</td>
                                    <td class="py-2">{{ \Illuminate\Support\Str::limit($leave->reason, 60) }}</td>
                                    <td class="py-2">
                                        <x-filament::badge
                                            :color="$leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning')">
                                            {{ $leave->status }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        </div>
    @else
        @php
            $summary = $this->summary();
            $rows = $this->rows();
        @endphp

        <div class="space-y-6">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="text-xs text-gray-500">From</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="from" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Until</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="to" />
                    </x-filament::input.wrapper>
                </div>
                <div class="text-xs text-gray-500">
                    {{ $summary['expected_visits'] }} visits expected from the team in this range.
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <x-filament::card>
                    <div class="text-xs text-gray-500">Active MRs</div>
                    <div class="text-xl font-bold">{{ $summary['active_mrs'] }}</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Checked in today</div>
                    <div class="text-xl font-bold">
                        {{ $summary['checked_in_today'] }} / {{ $summary['active_mrs'] }}
                    </div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Visits today</div>
                    <div class="text-xl font-bold">{{ $summary['visits_today'] }}</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Target achievement</div>
                    <div class="text-xl font-bold">{{ $summary['achievement'] }}%</div>
                    <div class="text-xs text-gray-500">{{ $summary['visits_range'] }} visits in range</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Doctors onboarded</div>
                    <div class="text-xl font-bold">{{ $summary['onboarded_range'] }}</div>
                    <div class="text-xs text-gray-500">In selected range</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Open assignments</div>
                    <div class="text-xl font-bold">{{ $summary['open_assignments'] }}</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Pending leaves</div>
                    <div class="text-xl font-bold">{{ $summary['pending_leaves'] }}</div>
                </x-filament::card>
                <x-filament::card>
                    <div class="text-xs text-gray-500">Visits in range</div>
                    <div class="text-xl font-bold">{{ $summary['visits_range'] }}</div>
                </x-filament::card>
            </div>

            <x-filament::section heading="Field team">
                @if ($rows->isEmpty())
                    <p class="text-sm text-gray-500">No active MR representative yet.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">MR</th>
                                <th class="py-2">Territory</th>
                                <th class="py-2">Today</th>
                                <th class="py-2">Visits today</th>
                                <th class="py-2">Visits in range</th>
                                <th class="py-2">Onboarded</th>
                                <th class="py-2">Open</th>
                                <th class="py-2">Last visit</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="border-t">
                                    <td class="py-2">
                                        <div class="font-medium">{{ $row['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['employee_code'] ?? '—' }}</div>
                                    </td>
                                    <td class="py-2">{{ $row['territory'] ?? '—' }}</td>
                                    <td class="py-2">
                                        <x-filament::badge
                                            :color="$row['status'] === 'Checked in' ? 'success' : ($row['status'] === 'Checked out' ? 'gray' : 'warning')">
                                            {{ $row['status'] }}
                                        </x-filament::badge>
                                        <div class="text-xs text-gray-500">{{ $row['check_in'] ?? '' }}</div>
                                    </td>
                                    <td class="py-2">{{ $row['visits_today'] }} / {{ $row['daily_target'] }}</td>
                                    <td class="py-2">{{ $row['visits_range'] }}</td>
                                    <td class="py-2">{{ $row['onboarded'] }}</td>
                                    <td class="py-2">
                                        {{ $row['open_assignments'] }}
                                        @if ($row['pending_leaves'])
                                            <span class="text-xs text-gray-500">(+{{ $row['pending_leaves'] }} leave)</span>
                                        @endif
                                    </td>
                                    <td class="py-2">{{ $row['last_visit'] ?? '—' }}</td>
                                    <td class="py-2">
                                        <x-filament::link
                                            tag="a"
                                            :href="$this->getUrl().'?mr='.$row['user_id']">
                                            Open
                                        </x-filament::link>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        </div>
    @endif
</x-filament::page>
