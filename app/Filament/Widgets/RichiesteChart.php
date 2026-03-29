<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\QuoteRequest as Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class RichiesteChart extends ChartWidget
{
    protected static ?string $heading = 'Richieste';

    public ?string $filter = 'tutte';
protected function getFilters(): ?array
{
    $user = auth()->user();
    $filters = [];

    if (in_array($user->role_id, [1,2])) { // ADMIN/SUPERADMIN
        $filters['tutti'] = 'Tutti gli agenti - ultimi 12 mesi';

        $agents = User::whereHas('role', fn($q) => $q->whereIn('nome',['agente','admin','superadmin']))
                      ->pluck('nome','id');

        foreach ($agents as $agentId => $name) {
            $filters[$agentId.'|tutti'] = $name . ' - ultimi 12 mesi';
        }
/*
        // Aggiungo i 12 mesi per filtro mese specifico
        for ($i = 0; $i < 12; $i++) {
            $month = now()->subMonths($i)->format('Y-m');
            $filters['tutti|'.$month] = 'Tutti gli agenti - ' . now()->subMonths($i)->format('M Y');
        }*/

    } else { // AGENTI normali
        for ($i = 0; $i < 12; $i++) {
            $month = now()->subMonths($i);
            $filters[$month->format('Y-m')] = $month->format('M Y');
        }
    }

    return $filters;
}

    protected function getData(): array
{
    $user = auth()->user();
    $activeFilter = $this->filter;

    $labels = [];
    $evaseData = [];
    $daGestireData = [];
    $scaduteData = [];

    // ---- LOGICA PER AGENTI ----
    if ($user->role->nome === 'agente') {
        $periods = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $periods[] = [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
                'label' => $date->format('M Y'),
            ];
        }

        foreach ($periods as $p) {
            $labels[] = $p['label'];
            $evaseData[] = $this->countEvase($user->id, $p['start'], $p['end']);
            $daGestireData[] = $this->countDaGestire($user->id, $p['start'], $p['end']);
            $scaduteData[] = $this->countScadute($user->id, $p['start'], $p['end']);
        }

    // ---- LOGICA PER AMMINISTRATORI ----
    } else {
        // Recupero agenti
        $agents = User::whereHas('role', fn($q) => $q->whereIn('nome', ['agente','admin','superadmin']))
                      ->get();

        // Determino periodo da filtrare
        $start = $end = null;
        if ($activeFilter && str_contains($activeFilter, '|')) {
            [$agentId, $monthPart] = explode('|', $activeFilter);
            if ($monthPart !== 'tutti') {
                $start = Carbon::createFromFormat('Y-m', $monthPart)->startOfMonth();
                $end = Carbon::createFromFormat('Y-m', $monthPart)->endOfMonth();
            }
        }
        if (!$start) {
            $start = Carbon::now()->subMonths(11)->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        }

        foreach ($agents as $agent) {
            $labels[] = $agent->nome;
            $evaseData[] = $this->countEvase($agent->id, $start, $end);
            $daGestireData[] = $this->countDaGestire($agent->id, $start, $end);
            $scaduteData[] = $this->countScadute($agent->id, $start, $end);
        }
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


    private function countEvase($agentId,$start,$end)
    {

        return Request::query()

            ->whereBetween('created_at',[$start,$end])

            ->whereHas('agenti_gestori',function($q) use ($agentId){
                $q->where('users.id',$agentId);
            })

            ->where('stato_richiesta','evasa')

            ->count();
    }


    private function countDaGestire($agentId,$start,$end)
    {

        return Request::query()

            ->whereBetween('created_at',[$start,$end])

            ->whereHas('managedByUsers',function($q) use ($agentId){
                $q->where('users.id',$agentId);
            })

            ->whereHas('agentAvailabilities',function($q) use ($agentId){
                $q->where('user_id',$agentId)
                  ->whereDate('n_giorni_preventivo','>',now());
            })

            ->whereIn('stato_richiesta',['in lavorazione','assegnata'])

            ->count();
    }


    private function countScadute($agentId,$start,$end)
    {

        return Request::query()

            ->whereBetween('created_at',[$start,$end])

            ->whereHas('managedByUsers',function($q) use ($agentId){
                $q->where('users.id',$agentId);
            })

            ->where(function($q) use ($agentId){

                $q->whereHas('agentAvailabilities',function($q2) use ($agentId){
                    $q2->where('user_id',$agentId)
                       ->whereDate('n_giorni_preventivo','<',now());
                })

                ->orWhereDoesntHave('agentAvailabilities',function($q3) use ($agentId){
                    $q3->where('user_id',$agentId);
                });

            })

            ->whereIn('stato_richiesta',['in lavorazione','assegnata'])

            ->count();
    }


    protected function getType(): string
    {
        return 'bar';
    }
}