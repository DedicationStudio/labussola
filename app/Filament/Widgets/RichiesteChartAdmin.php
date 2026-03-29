<?php

namespace App\Filament\Widgets;

use App\Models\QuoteRequest as Request;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class RichiesteChartAdmin extends ChartWidget
{
    protected static ?string $heading = 'Richieste per Agente';


    public $start_date;
    public $end_date;
    public $agent_id;

    protected static ?string $pollingInterval = null;

    protected $listeners = [
    'filtersUpdated' => 'updateFilters',
];


    public function updateFilters($filters)
    {
        $this->start_date = $filters['start_date'];
        $this->end_date = $filters['end_date'];
        $this->agent_id = $filters['agent_id'];

    $this->updateChartData(); // 🔥 QUESTO è quello giusto

        //dd($filters);

    }
    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m');
        $this->end_date = now()->format('Y-m');
        $this->agent_id = null;
    }

    protected function getData(): array
    {
        $user = auth()->user();
        if (!in_array($user->role_id, [1, 2])) {
            return []; // solo amministratori
        }

        $start = $this->start_date ?? now()->subYear();
        $end = $this->end_date ?? now();
        $agentId = $this->agent_id;
        $periods = [];
        $labels = [];
        $evaseData = [];
        $daGestireData = [];
        $scaduteData = [];

        $current = \Carbon\Carbon::parse($this->start_date)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($this->end_date)->endOfMonth();

        while ($current <= $endDate) {
            $periods[] = [
                'start' => $current->copy()->startOfMonth(),
                'end' => $current->copy()->endOfMonth(),
                'label' => $current->format('M Y'),
            ];
            $current->addMonth();
        }

        foreach ($periods as $p) {
            $labels[] = $p['label'];
            $evaseData[] = $this->countEvase($agentId, $p['start'], $p['end']);
            $daGestireData[] = $this->countDaGestire($agentId, $p['start'], $p['end']);
            $scaduteData[] = $this->countScadute($agentId, $p['start'], $p['end']);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Evase', 'data' => $evaseData, 'backgroundColor' => '#22c55e'],
                ['label' => 'Da gestire', 'data' => $daGestireData, 'backgroundColor' => '#9ca3af'],
                ['label' => 'Scadute', 'data' => $scaduteData, 'backgroundColor' => '#ef4444'],
            ],
        ];
    }


    private function countEvase($agentId, $start, $end)
    {

        return Request::query()

            ->whereBetween('created_at', [$start, $end])

            ->whereHas('agenti_gestori', function ($q) use ($agentId) {
                $q->where('users.id', $agentId);
            })

            ->where('stato_richiesta', 'evasa')

            ->count();
    }


    private function countDaGestire($agentId, $start, $end)
    {

        return Request::query()

            ->whereBetween('created_at', [$start, $end])

            ->whereHas('managedByUsers', function ($q) use ($agentId) {
                $q->where('users.id', $agentId);
            })

            ->whereHas('agentAvailabilities', function ($q) use ($agentId) {
                $q->where('user_id', $agentId)
                    ->whereDate('n_giorni_preventivo', '>', now());
            })

            ->whereIn('stato_richiesta', ['in lavorazione', 'assegnata'])

            ->count();
    }


    private function countScadute($agentId, $start, $end)
    {

        return Request::query()

            ->whereBetween('created_at', [$start, $end])

            ->whereHas('managedByUsers', function ($q) use ($agentId) {
                $q->where('users.id', $agentId);
            })

            ->where(function ($q) use ($agentId) {

                $q->whereHas('agentAvailabilities', function ($q2) use ($agentId) {
                    $q2->where('user_id', $agentId)
                        ->whereDate('n_giorni_preventivo', '<', now());
                })

                    ->orWhereDoesntHave('agentAvailabilities', function ($q3) use ($agentId) {
                        $q3->where('user_id', $agentId);
                    });
            })

            ->whereIn('stato_richiesta', ['in lavorazione', 'assegnata'])

            ->count();
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
