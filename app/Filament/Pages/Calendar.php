<?php

namespace App\Filament\Pages;

use App\Models\AgentAvailability;
use Filament\Pages\Page;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class Calendar extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $view = 'filament.pages.calendar';

    protected static ?string $navigationLabel = 'Calendario';
    protected static ?string $title = 'Calendario';
    protected static ?int $navigationSort = 1;

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(AgentAvailability::query())
            ->modifyQueryUsing(fn (Builder $query) => 
                $query->with('user')
                    ->whereNotNull('start_date')
                    ->whereNotNull('end_date')
                    ->whereNotNull('note')
                    ->where('note', '!=', '')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.nome')
                    ->label('Utente')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Dal giorno')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Al giorno')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Ora inizio')
                    ->formatStateUsing(function ($state) {
                        if (!$state || $state === '-') {
                            return '-';
                        }
                        try {
                            return \Carbon\Carbon::parse($state)->format('H:i');
                        } catch (\Exception $e) {
                            return '-';
                        }
                    }),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Ora fine')
                    ->formatStateUsing(function ($state) {
                        if (!$state || $state === '-') {
                            return '-';
                        }
                        try {
                            return \Carbon\Carbon::parse($state)->format('H:i');
                        } catch (\Exception $e) {
                            return '-';
                        }
                    }),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->note)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('stato')
                    ->label('Stato')
                    ->colors([
                        'success' => 'disponibile',
                        'danger' => 'non_disponibile',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'disponibile' => 'Disponibile',
                        'non_disponibile' => 'Non disponibile',
                        default => $state,
                    }),
            ])
            ->persistFiltersInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('stato')
                    ->label('Filtra per stato')
                    ->options([
                        'disponibile' => 'Disponibile',
                        'non_disponibile' => 'Non disponibile',
                    ])
                    ->placeholder('Tutti'),

                Tables\Filters\Filter::make('start_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('data_da')
                            ->label('Dal giorno'),
                        \Filament\Forms\Components\DatePicker::make('data_a')
                            ->label('Al giorno'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_da'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['data_a'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['data_da'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dal: ' . \Carbon\Carbon::parse($data['data_da'])->format('d/m/Y'))
                                ->removeField('data_da');
                        }
                        if ($data['data_a'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Al: ' . \Carbon\Carbon::parse($data['data_a'])->format('d/m/Y'))
                                ->removeField('data_a');
                        }
                        return $indicators;
                    }),
            ])
        
            ->bulkActions([])
            ->defaultSort('start_date', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->striped();
    }
}