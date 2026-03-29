<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Preventive;
use Illuminate\Support\Facades\Log;

class PreventiveSeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = fopen(base_path("database/seeders/csv/preventives.csv"), "r");

        // Salta la prima riga con intestazioni
        //fgetcsv($csvFile, 0, ";");

        while (($data = fgetcsv($csvFile, 0, ";")) !== FALSE) {
            try {
                Preventive::create([
                    "quote_request_id" => (!empty($data[1]) && $data[1] !== "NULL" && $data[1] != 0) ? intval($data[1]) : null,
                    "created_by" => (!empty($data[2]) && $data[2] !== "NULL" && $data[2] != 0) ? intval($data[2]) : null,
                    "customer_id" => (!empty($data[3]) && $data[3] !== "NULL" && $data[3] != 0) ? intval($data[3]) : null,
                    "itinerary_id" => (!empty($data[4]) && $data[4] !== "NULL" && $data[4] != 0) ? intval($data[4]) : null,
                    "nome_itinerario" => $data[5] ?? null,
                    "itinerario" => $data[6] ?? null,
                    "oggetto" => $data[7] ?? null,
                    "numero" => (!empty($data[8]) && $data[8] !== "NULL") ? intval($data[8]) : null,
                    "anno" => (!empty($data[9]) && $data[9] !== "NULL") ? intval($data[9]) : null,
                    "data_preventivo" => (!empty($data[10]) && $data[10] !== "NULL") ? $data[10] : null,
                    "data_invio" => (!empty($data[11]) && $data[11] !== "NULL") ? $data[11] : null,
                    "tag" => $data[12] ?? null,
                    "email_cliente" => $data[13] ?? null,
                    "numero_persone" => (!empty($data[14]) && $data[14] !== "NULL") ? intval($data[14]) : null,
                    "prezzo_per_persona" => (!empty($data[15]) && $data[15] !== "NULL") ? intval($data[15]) : null,
                    "numero_gratuita" => (!empty($data[16]) && $data[16] !== "NULL") ? intval($data[16]) : null,
                    "markup" => (!empty($data[17]) && $data[17] !== "NULL") ? intval($data[17]) : null,
                    "data_inizio_viaggio" => (!empty($data[18]) && $data[18] !== "NULL") ? $data[18] : null,
                    "data_fine_viaggio" => (!empty($data[19]) && $data[19] !== "NULL") ? $data[19] : null,
                    "luogo_di_partenza_andata" => $data[20] ?? null,
                    "luogo_di_arrivo_andata" => $data[21] ?? null,
                    "data_ora_partenza_andata" => (!empty($data[22]) && $data[22] !== "NULL") ? $data[22] : null,
                    "data_ora_arrivo_andata" => (!empty($data[23]) && $data[23] !== "NULL") ? $data[23] : null,
                    "luogo_di_partenza_rientro" => $data[24] ?? null,
                    "luogo_di_arrivo_rientro" => $data[25] ?? null,
                    "data_ora_partenza_rientro" => (!empty($data[26]) && $data[26] !== "NULL") ? $data[26] : null,
                    "data_ora_arrivo_rientro" => (!empty($data[27]) && $data[27] !== "NULL") ? $data[27] : null,
                    "foto_introduttiva" => $data[28] ?? null,
                    "immagini" => $data[29] ?? null,
                    "n_persone_forzato" => (!empty($data[30]) && $data[30] !== "NULL") ? intval($data[30]) : null,
                    "prezzo_forzato" => (!empty($data[31]) && $data[31] !== "NULL") ? intval($data[31]) : null,
                    "costo_polizza" => (!empty($data[32]) && $data[32] !== "NULL") ? intval($data[32]) : null,
                    "quota_comprende_generico" => $data[33] ?? null,
                    "quota_non_comprende_generico" => $data[34] ?? null,
                    "files_pratica_accettata" => $data[35] ?? null,
                    "file_personalizzato" => $data[36] ?? null,
                    "file_polizza" => $data[37] ?? null,
                    "date_expiration" => (!empty($data[38]) && $data[38] !== "NULL") ? $data[38] : null,
                    "note" => $data[39] ?? null,
                    "stato" => $data[40] ?? 'in attesa',
                    "stato_altro_testo" => (!empty($data[41]) && $data[41] !== "NULL") ? $data[41] : null,
                    "created_at" => (!empty($data[42]) && $data[42] !== "NULL") ? $data[42] : now(),
                    "updated_at" => (!empty($data[43]) && $data[43] !== "NULL") ? $data[43] : now(),
                ]);
            } catch (\Exception $e) {
                Log::error("Preventivo saltato: " . json_encode($data) . " → " . $e->getMessage());
            }

        }


        fclose($csvFile);
    }
}
