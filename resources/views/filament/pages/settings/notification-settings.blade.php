<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button wire:click="save">
                Save settings
            </x-filament::button>
        </div>
    </div>
</x-filament::page>

