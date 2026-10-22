<x-filament::page>
    @php
        $rows = $this->rows();
        $mrs = $this->mrOptions();
        $types = [
            '' => 'All types',
            'gp' => 'GP',
            'specialist' => 'Specialist',
            'diagnostic_center' => 'Diagnostic Center',
            'hospital' => 'Hospital',
        ];
    @endphp

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="text-xs text-gray-500">MR</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="mrFilter">
                        <option value="">All MRs</option>
                        @foreach ($mrs as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <label class="text-xs text-gray-500">Type</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="typeFilter">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <label class="text-xs text-gray-500">Search name or mobile</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model.live.debounce.400ms="search" placeholder="e.g. Patil / 98..." />
                </x-filament::input.wrapper>
            </div>
        </div>

        <x-filament::section heading="Field registrations">
            @if ($rows->isEmpty())
                <p class="text-sm text-gray-500">No doctor onboarded by an MR matches these filters.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2">Type</th>
                            <th class="py-2">Name</th>
                            <th class="py-2">Details</th>
                            <th class="py-2">Area</th>
                            <th class="py-2">Mobile</th>
                            <th class="py-2">Onboarded by</th>
                            <th class="py-2">Date</th>
                            <th class="py-2">Re-assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-t">
                                <td class="py-2">
                                    <x-filament::badge>{{ $row['type'] }}</x-filament::badge>
                                </td>
                                <td class="py-2">{{ $row['name'] ?? '—' }}</td>
                                <td class="py-2">{{ $row['detail'] ?? '—' }}</td>
                                <td class="py-2">{{ $row['city'] ?? '—' }}</td>
                                <td class="py-2">{{ $row['mobile'] ?? '—' }}</td>
                                <td class="py-2">{{ $row['mr_name'] ?? '—' }}</td>
                                <td class="py-2">{{ $row['created_at']?->format('d M Y') ?? '—' }}</td>
                                <td class="py-2">
                                    <div class="flex items-center gap-2">
                                        <x-filament::input.wrapper>
                                            <x-filament::input.select wire:model="reassign.{{ $row['key'] }}">
                                                <option value="">Select MR</option>
                                                @foreach ($mrs as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </x-filament::input.select>
                                        </x-filament::input.wrapper>
                                        <x-filament::button
                                            size="xs"
                                            wire:click="saveReassign('{{ $row['key'] }}')">
                                            Save
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
