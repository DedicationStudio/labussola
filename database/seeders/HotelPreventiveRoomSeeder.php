<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HotelPreventiveRoom;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HotelPreventiveRoomSeeder extends Seeder
{
    public function run(): void
    {
        $this->importCsv(base_path("database/seeders/csv/hotel_a_persona.csv"));
        $this->importCsv(base_path("database/seeders/csv/hotel_per_notte.csv"));
    }

    private function importCsv(string $filePath): void
    {
        if (!file_exists($filePath)) {
            Log::warning("File non trovato: " . $filePath);
            return;
        }

        $csvFile = fopen($filePath, "r");
        // Se il file ha intestazione → decommenta
        // fgetcsv($csvFile, 0, ";");

        while (($data = fgetcsv($csvFile, 0, ";")) !== false) {
            try {
                $hotelPreventiveId = (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null;

                //  controllo FK
                if ($hotelPreventiveId && !DB::table('hotel_preventive')->where('id', $hotelPreventiveId)->exists()) {
                    Log::warning("HotelPreventive {$hotelPreventiveId} non trovato → salto riga", $data);
                    continue;
                }

                //  Debug sul tipo_costo
                Log::debug("TIPO COSTO RAW", [
                    'raw' => $data[3] ?? null,
                    'normalized' => $this->normalizeTipoCosto($data[3] ?? null),
                ]);

                HotelPreventiveRoom::create([
                    "hotel_preventive_id" => $hotelPreventiveId,
                    "tipologia_stanza" => $data[2] ?? null,
                    "tipo_costo" => $this->normalizeTipoCosto($data[3] ?? null),
                    "quantita_camere" => (!empty($data[4]) && $data[4] !== "NULL") ? intval($data[4]) : null,
                    "n_notti" => (!empty($data[5]) && $data[5] !== "NULL") ? intval($data[5]) : null,
                    "costo_notte" => (!empty($data[6]) && $data[6] !== "NULL") ? floatval($data[6]) : null,
                ]);
            } catch (\Exception $e) {
                Log::error("Errore HotelPreventiveRoom → " . json_encode($data) . " → " . $e->getMessage());
            }
        }

        fclose($csvFile);
    }
    private function normalizeTipoCosto(?string $val): string
    {
        $val = strtolower(trim($val ?? ''));

        $map = [
            'a_persona' => 'a persona',
            'a persona' => 'a persona',
            'a_camera' => 'a camera',
            'a camera' => 'a camera',
            'una_tantum' => 'una tantum',
            'una tantum' => 'una tantum',
        ];

        return $map[$val] ?? 'a persona'; // fallback
    }

}
