<?php

namespace App\Filament\Pages;


use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class DashboardStats extends Page
{
    //protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = "Dahsboard Agenti";
    protected static string $view = 'filament.pages.dashboard-stats';

    // qui definisci proprietà iniziali dei filtri
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $agent_id = null;

    public function updated($property)
    {
        $this->dispatch('filtersUpdated', [
    'start_date' => $this->start_date,
    'end_date' => $this->end_date,
    'agent_id' => $this->agent_id,
]);
    }
    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->end_date = now()->toDateString();

        $this->agent_id = \App\Models\User::whereHas('role', fn($q) => $q->where('nome', 'agente'))
            ->value('id');
    }

protected function getFormSchema(): array
{
    $user = auth()->user();

    $isAdmin = $user->role?->nome === 'admin' || $user->role?->nome === 'superadmin';

    $agents = \App\Models\User::whereHas('role', fn($q) => $q->where('nome', 'agente'))
        ->pluck('nome', 'id');

    return [
        \Filament\Forms\Components\DatePicker::make('start_date')
            ->label('Data inizio')
            ->displayFormat('m/Y')
            ->format('Y-m')
            ->closeOnDateSelection()
            ->native(false)
            ->reactive()
            ->required()
            ->visible($isAdmin),

        \Filament\Forms\Components\DatePicker::make('end_date')
            ->label('Data fine')
            ->displayFormat('m/Y')
            ->format('Y-m')
            ->closeOnDateSelection()
            ->native(false)
            ->reactive()
            ->required()
            ->visible($isAdmin),

        \Filament\Forms\Components\Select::make('agent_id')
            ->label('Agente')
            ->reactive()
            ->options($agents)
            ->required()
            ->visible($isAdmin),
    ];
}


    protected function headerWidgets(): array
    {
        $widgets = [
            \App\Filament\Widgets\RichiesteChart::class,
        ];

        if (in_array(auth()->user()->role_id, [1, 2])) {
            $widgets[] = \App\Filament\Widgets\RichiesteChartAdmin::class;
        }

        return $widgets;
    }
}
/*
use Filament\Pages\Page;

class DashboardStats extends Page
{
    //protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title="Dahsboard Agenti";
    protected static string $view = 'filament.pages.dashboard-stats';

    protected function getHeaderWidgets(): array
{
    return [
        \App\Filament\Widgets\RichiesteChart::class,
    ];
}
}*/

/*<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class DashboardStats extends Page
{
    //protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = "Dahsboard Agenti";
    protected static string $view = 'filament.pages.dashboard-stats';

    // qui definisci proprietà iniziali dei filtri
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $agent_id = null;

    public function updated($property)
    {
        $this->dispatch('refreshWidgets');
    }
    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->end_date = now()->toDateString();

        $this->agent_id = \App\Models\User::whereHas('role', fn($q) => $q->where('nome', 'agente'))
            ->value('id');
    }

    protected function getFormSchema(): array
    {
        $agents = \App\Models\User::whereHas('role', fn($q) => $q->where('nome', 'agente'))
            ->pluck('nome', 'id');

        return [
            \Filament\Forms\Components\DatePicker::make('start_date')
                ->label('Data inizio')
                ->reactive()
                ->required(),
            \Filament\Forms\Components\DatePicker::make('end_date')
                ->label('Data fine')
                ->reactive()
                ->required(),
            \Filament\Forms\Components\Select::make('agent_id')
                ->label('Agente')
                ->reactive()
                ->options($agents)
                ->required(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        $widgets = [
            \App\Filament\Widgets\RichiesteChart::class,
        ];

        if (in_array(auth()->user()->role_id, [1, 2])) {
            $widgets[] = \App\Filament\Widgets\RichiesteChartAdmin::class;
        }

        return $widgets;
    }
}

*/