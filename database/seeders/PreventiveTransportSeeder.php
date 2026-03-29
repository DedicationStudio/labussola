<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PreventiveTransport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PreventiveTransportSeeder extends Seeder
{
    public function run(): void
    {
        $this->importCsv("database/seeders/csv/trasporto_per_persona.csv");
        $this->importCsv("database/seeders/csv/trasporto_una_tantum.csv");
    }

    private function importCsv(string $path): void
    {
        $csvFile = fopen(base_path($path), "r");

        while (($data = fgetcsv($csvFile, 0, ";")) !== false) {
            try {
                $preventiveId = (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null;
                $transportId  = (!empty($data[2]) && $data[2] !== "NULL") ? intval($data[2]) : null;
                $companyId    = (!empty($data[3]) && $data[3] !== "NULL") ? intval($data[3]) : null;

                // controllo FK preventives
                if ($preventiveId && !DB::table('preventives')->where('id', $preventiveId)->exists()) {
                    Log::warning("Preventive {$preventiveId} non trovato → salto riga", $data);
                    continue;
                }

                //  controllo FK transports
                if ($transportId && !DB::table('transports')->where('id', $transportId)->exists()) {
                    Log::warning("Transport {$transportId} non trovato → lo metto a NULL", $data);
                    $transportId = null;
                }

                // controllo FK transport_companies
                if ($companyId && !DB::table('transport_companies')->where('id', $companyId)->exists()) {
                    Log::warning("TransportCompany {$companyId} non trovata → la metto a NULL", $data);
                    $companyId = null;
                }

                PreventiveTransport::create([
                    "preventive_id"           => $preventiveId,
                    "transport_id"            => $transportId,
                    "transport_company_id"    => $companyId,

                    "direzione_trasporto"     => $this->normalizeDirezione($data[4] ?? null),
                    "tipo_trasporto"          => $this->normalizeTipoTrasporto($data[5] ?? null),
                    "tipo_costo"              => $this->normalizeTipoCosto($data[6] ?? null),

                    "prezzo"                  => (!empty($data[7]) && $data[7] !== "NULL") ? intval($data[7]) : null,
                    "scorpora_trasporto"      => (!empty($data[8]) && $data[8] !== "NULL") ? (bool) $data[8] : false,
                    "kg_bg_a_mano"            => (!empty($data[9]) && $data[9] !== "NULL") ? intval($data[9]) : null,
                    "kg_bg_in_stiva"          => (!empty($data[10]) && $data[10] !== "NULL") ? intval($data[10]) : null,
                    "misura_bg_a_mano"        => $data[11] ?? null,
                    "quota_comprende_trasporti"    => $data[12] ?? null,
                    "quota_non_comprende_trasporti" => $data[13] ?? null,
                    "file_fornitore_trasporto" => (!empty($data[14]) && $data[14] !== "NULL")
                        ? $data[14]
                        : json_encode([]),
                    "note" => $data[15] ?? null,
                ]);
            } catch (\Exception $e) {
                Log::error("Errore PreventiveTransport → " . json_encode($data) . " → " . $e->getMessage());
            }
        }

        fclose($csvFile);
    }

    //  funzioni di normalizzazione
    private function normalizeDirezione($val): string
    {
        $val = strtolower(trim($val ?? ''));
        $map = [
            'andata'     => 'andata',
            'ritorno'    => 'rientro',
            'rientro'    => 'rientro',
            'intermedio' => 'intermedio',
        ];
        return $map[$val] ?? 'andata';
    }

    private function normalizeTipoTrasporto($val): string
    {
        $val = strtolower(trim($val ?? ''));
        if ($val === '' || $val === 'null') {
            return 'altro';
        }
        $map = [
            'aereo' => 'aereo',
            'bus'   => 'bus',
            'treno' => 'treno',
            'altro' => 'altro',
        ];
        return $map[$val] ?? 'altro';
    }

    private function normalizeTipoCosto($val): string
    {
        $val = strtolower(trim($val ?? ''));
        $map = [
            'una tantum' => 'una tantum',
            'a persona'  => 'a persona',
        ];
        return $map[$val] ?? 'una tantum';
    }
}
