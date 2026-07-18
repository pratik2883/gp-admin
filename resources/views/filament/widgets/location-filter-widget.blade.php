<x-filament::widget>
    <x-filament::card>
        <div class="flex items-end gap-3 flex-wrap">
            <div class="w-full sm:w-80">
                <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Search by Location
                </label>
                <select
                    id="city"
                    wire:model.defer="city"
                    wire:change="apply"
                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-primary-600 text-sm"
                >
                    <option value="">Select Location</option>
                    @foreach ($cities as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <x-filament::button class="h-10" wire:click="apply">
                Apply
            </x-filament::button>

            <x-filament::button class="h-10" color="gray" wire:click="resetFilter">
                Reset
            </x-filament::button>
        </div>
    </x-filament::card>
</x-filament::widget>
