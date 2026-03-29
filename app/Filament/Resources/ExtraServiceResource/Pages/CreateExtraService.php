<?php

namespace App\Filament\Resources\ExtraServiceResource\Pages;

use App\Models\ExtraService;
use App\Filament\Resources\ExtraServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExtraService extends CreateRecord
{
    protected static string $resource = ExtraServiceResource::class;

    protected static ?string $title = 'Nuovo Servizio Extra';

public function mount(): void
{
    parent::mount();

    $data = [];

    // 🔹 DUPLICAZIONE SERVIZIO
    if ($id = request('duplicate_from_service')) {

        $source = ExtraService::query()->find($id);

        if ($source) {
            $data = array_merge(
                $source->duplicatedAttributes(),
                [
                    'supplier_id' => $source->supplier_id,
                    // qui puoi già iniziare a ragionare da prodotto serio
                    'tipo' => is_array($source->tipo)
                        ? $source->tipo
                        : json_decode($source->tipo ?? '[]', true),
                    'nome' => $source->nome . ' (Copia)',
                    'descrizione_servizio' => $source->descrizione_servizio,
                    'icon' => $source->icon,
                ]
            );
        }
    }

    // 🔹 FORZO FORNITORE (utile se entri da contesto)
    if ($supplierId = request('supplier_id')) {
        $data['supplier_id'] = $supplierId;
    }

    // 🔹 DEFAULT (se ti serviranno in futuro)
    // esempio:
    // $data['attivo'] ??= true;

    $this->form->fill($data);
}
}