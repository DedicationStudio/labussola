<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PreventiveExtraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PreventiveExtraServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Importa i servizi "a persona"
        $this->importCsv("database/seeders/csv/extra_service_a_persona.csv", "a persona");

        // Importa i servizi "una tantum"
        $this->importCsv("database/seeders/csv/extra_service_una_tantum.csv", "una tantum");
    }

    private function importCsv(string $path, string $tipoCosto): void
    {
        if (!file_exists(base_path($path))) {
            Log::warning("File non trovato: " . $path);
            return;
        }

        $csvFile = fopen(base_path($path), "r");
        // Se hai intestazione → decommenta
        // fgetcsv($csvFile, 0, ";");

        while (($data = fgetcsv($csvFile, 0, ";")) !== false) {
            try {
                //  Debug: vedi esattamente cosa arriva
                Log::debug("Row CSV ({$tipoCosto})", ['raw' => $data]);

                $preventiveId = (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null;
                $extraServiceId = (!empty($data[2]) && $data[2] !== "NULL") ? intval($data[2]) : null;

                // controllo FK preventives
                if ($preventiveId && !DB::table('preventives')->where('id', $preventiveId)->exists()) {
                    Log::warning("Preventive {$preventiveId} non trovato → salto riga", $data);
                    continue;
                }

                // controllo FK extra_services
                if ($extraServiceId && !DB::table('extra_services')->where('id', $extraServiceId)->exists()) {
                    Log::warning("ExtraService {$extraServiceId} non trovato → lo metto a NULL", $data);
                    $extraServiceId = null;
                }

                PreventiveExtraService::create([
                    "preventive_id" => $preventiveId,
                    "extra_service_id" => $extraServiceId,
                    "tipo_costo" => $this->normalizeTipoCosto($tipoCosto),
                    "prezzo" => (!empty($data[3]) && $data[3] !== "NULL") ? floatval($data[3]) : null,
                    "quantita" => (!empty($data[4]) && $data[4] !== "NULL") ? intval($data[4]) : null,
                    "quantita_a_persona" => (!empty($data[5]) && $data[5] !== "NULL") ? intval($data[5]) : null,
                    "scorpora_servizio" => (!empty($data[6]) && $data[6] !== "NULL") ? (bool) $data[6] : false,
                    "descrizione_servizio" => $data[8] ?? null,
                    "quota_comprende_servizi" => $data[9] ?? null,
                    "quota_non_comprende_servizi" => $data[10] ?? null,
                    "file_fornitore_servizi_extra" => (!empty($data[11]) && $data[9] !== "NULL")
                        ? $data[11]
                        : json_encode([]),
                    "note" => $data[12] ?? null,

                ]);
            } catch (\Exception $e) {
                Log::error("Errore PreventiveExtraService ({$tipoCosto}) → " . json_encode($data) . " → " . $e->getMessage());
            }
        }

        fclose($csvFile);
    }

    //  normalizzazione tipo_costo
    private function normalizeTipoCosto(string $val): string
    {
        $val = strtolower(trim($val ?? ''));

        $map = [
            'a_persona' => 'a_persona',
            'a persona' => 'a_persona',
            'una_tantum' => 'una_tantum',
            'una tantum' => 'una_tantum',
        ];

        return $map[$val] ?? 'una_tantum';
    }
}
