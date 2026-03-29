<?php

namespace App\Filament\Widgets;

use App\Models\AgentAvailability;
use Filament\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class AgentAvailabilityCalendar extends FullCalendarWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;
    protected static array $plugins = [
        'interaction',
        'dayGrid',
        'timeGrid',
        'list',
    ];

    public Model|string|null $model = AgentAvailability::class;
    public bool $editing = false;

    public function resolveEventRecord(array $data): ?Model
    {
        \Log::info('resolveEventRecord chiamato con:', $data);

        $id = $data['id'] ?? null;

        if (!$id) {
            \Log::error('Nessun ID in resolveEventRecord');
            return null;
        }

        if (strpos($id, '-') !== false) {
            $id = explode('-', $id)[0];
        }

        $record = AgentAvailability::find($id);
        \Log::info('Record trovato:', $record ? $record->toArray() : ['nessuno']);

        return $record;
    }

    public function getMountedAction(): ?Actions\Action
    {
        $action = parent::getMountedAction();

        if ($action?->getName() === 'view') {
            $action->modalHeading('Visualizza Disponibilità');
        }

        if ($action?->getName() === 'edit') {
            $action->modalHeading('Modifica Disponibilità');
        }

        if ($action?->getName() === 'create') {
            $action->modalHeading('Nuova Disponibilità');
        }

        return $action;
    }

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'buttonText' => [
                'today' => 'Oggi',
                'month' => 'Mese',
                'week' => 'Settimana',
                'day' => 'Giorno',
            ],
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '22:00:00',
            'slotDuration' => '00:30:00',
            'allDaySlot' => true,
            'nowIndicator' => true,
            'locale' => 'it',
            'timeZone' => 'Europe/Rome',
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
            'slotLabelFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
            'editable' => true,
            'selectable' => true,
            'selectMirror' => true,
            'dayMaxEvents' => true,
            'navLinks' => true,
            'height' => 'auto',
            'contentHeight' => 'auto',
            'displayEventTime' => false,
        ];
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\Hidden::make('user_id')
                ->default(fn() => Auth::id())
                ->dehydrated(),

            Forms\Components\DatePicker::make('start_date')
                ->label('Dal giorno')
                ->required(),

            Forms\Components\DatePicker::make('end_date')
                ->label('Al giorno')
                ->required()
                ->afterOrEqual('start_date'),

            Forms\Components\TimePicker::make('start_time')
                ->seconds(false)
                ->label('Ora inizio'),

            Forms\Components\TimePicker::make('end_time')
                ->seconds(false)
                ->label('Ora fine')
                ->after('start_time'),

            Forms\Components\Textarea::make('note')
                ->columnSpanFull()
                ->required()
                ->label('Note'),

            Forms\Components\ToggleButtons::make('stato')
                ->options([
                    'disponibile' => 'Disponibile',
                    'non_disponibile' => 'Non disponibile',
                ])
                ->default('disponibile')
                ->inline(),
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $events = [];

        AgentAvailability::with('user')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereNotNull('note')
            ->where('note', '!=', '')
            ->get()
            ->each(function ($availability) use (&$events) {

                if ($availability->start_time && $availability->end_time) {
                    $startDate = Carbon::parse($availability->start_date);
                    $endDate = Carbon::parse($availability->end_date);

                    // Calcola il range di date per il titolo
                    $dateRange = $startDate->format('H:m') . '-' . $endDate->format('H:m');

                    // Itera su ogni giorno nel range
                    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                        $rawData = \DB::table('agent_availabilities')
                            ->where('id', $availability->id)
                            ->first();

                        $startTime = $rawData->start_time; // Orario GREZZO dal database
                        $endTime = $rawData->end_time;     // Orario GREZZO dal database
    
                        $start = $date->format('Y-m-d') . ' ' . $startTime;
                        $end = $date->format('Y-m-d') . ' ' . $endTime;

                        $events[] = [
                            'id' => $availability->id . '-' . $date->format('Y-m-d'),
                            'title' => substr($startTime, 0, 5) . '-' . substr($endTime, 0, 5) . ' '
                                . ($availability->user?->nome ?? 'Sconosciuto')
                                . ($availability->note ? ' - ' . ucfirst($availability->note) : ''),
                            'start' => $start,
                            'end' => $end,
                            'backgroundColor' => $availability->stato === 'disponibile' ? '#16a34a' : '#dc2626',
                            'textColor' => '#fff',
                            'allDay' => false,
                            'display' => 'block',
                        ];
                    }
                } else {
                    // Per disponibilità senza fasce orarie (eventi "tutto il giorno")
                    $start = Carbon::parse($availability->start_date);
                    $end = Carbon::parse($availability->end_date)->addDay();

                    $events[] = [
                        'id' => $availability->id,
                        'title' => ($availability->user?->nome ?? 'Sconosciuto')
                            . ($availability->note ? ' - ' . ucfirst($availability->note) : ''),
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d'),
                        'backgroundColor' => $availability->stato === 'disponibile' ? '#16a34a' : '#dc2626',
                        'textColor' => '#fff',
                        'allDay' => true,
                    ];
                }
            });

        return $events;
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifica disponibilità')
                ->form($this->getFormSchema())
                ->mountUsing(function (AgentAvailability $record, Forms\Form $form) {
                    \Log::info('mountUsing chiamato per record:', $record->toArray());

                    $form->fill([
                        'user_id' => $record->user_id,
                        'start_date' => $record->start_date,
                        'end_date' => $record->end_date,
                        'start_time' => $record->start_time,
                        'end_time' => $record->end_time,
                        'note' => $record->note,
                        'stato' => $record->stato,
                    ]);
                })
                ->using(function (AgentAvailability $record, array $data) {
                    if ($record->user_id !== Auth::id()) {
                        Notification::make()
                            ->title('Non puoi modificare questa disponibilità')
                            ->danger()
                            ->send();
                        return;
                    }

                    $record->update($data);

                    Notification::make()
                        ->title('Disponibilità aggiornata!')
                        ->success()
                        ->send();

                    $this->refreshRecords();
                })
                ->visible(fn(AgentAvailability $record) => $record->user_id === Auth::id())
                ->successRedirectUrl(url()->previous()),

            Actions\DeleteAction::make()
                ->after(function () {
                    Notification::make()
                        ->title('Disponibilità eliminata!')
                        ->success()
                        ->send();
                    $this->refreshRecords();
                })
                ->modalHeading(fn($record): string => 'Elimina Evento')
                ->visible(function (AgentAvailability $record) {
                    $user = Auth::user();


                    return $record->user_id === $user->id
                        || $user->hasAnyRole(['admin', 'superadmin']);
                })
                ->successRedirectUrl(url()->previous()),
        ];
    }
}