{{-- <x-filament-panels::page />
 --}}
 <x-filament-panels::page>
    {{-- Calendario in alto --}}
    @livewire(\App\Filament\Widgets\AgentAvailabilityCalendar::class)

    {{-- Tabella sotto --}}
    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
