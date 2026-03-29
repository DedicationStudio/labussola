<x-filament-panels::page>
    <div class="mb-6">
        {{ $this->form }}
    </div>

    <x-filament-widgets::widgets
        :widgets="$this->headerWidgets()"
        :columns="2" />
</x-filament-panels::page>