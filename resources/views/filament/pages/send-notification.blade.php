<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button wire:click="send" icon="heroicon-m-paper-airplane">
                Send notification
            </x-filament::button>
        </div>
    </div>
</x-filament::page>