<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HotelPreventive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HotelPreventiveSeeder extends Seeder
{
    public function run(): void
    {
        $this->importCsv(base_path("database/seeders/csv/hotel_preventive.csv"));
    }

    private function importCsv(string $filePath): void
    {
        if (!file_exists($filePath)) {
            Log::warning("File non trovato: " . $filePath);
            return;
        }

        $csvFile = fopen($filePath, "r");
        // Se hai intestazione, scommenta la prossima riga
        // fgetcsv($csvFile, 0, ";");

        while (($data = fgetcsv($csvFile, 0, ";")) !== false) {
            try {
                $preventiveId = (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null;
                $hotelId      = (!empty($data[2]) && $data[2] !== "NULL") ? intval($data[2]) : null;

                // Controllo preventives
                if ($preventiveId && !DB::table('preventives')->where('id', $preventiveId)->exists()) {
                    Log::warning("Preventive {$preventiveId} non trovato → salto riga", $data);
                    continue;
                }

                //  Controllo hotels
                if ($hotelId && !DB::table('hotels')->where('id', $hotelId)->exists()) {
                    Log::warning("Hotel {$hotelId} non trovato → lo metto a NULL", $data);
                    $hotelId = null;
                }

                HotelPreventive::create([
                    "preventive_id"            => $preventiveId,
                    "hotel_id"                 => $hotelId,
                    "quota_comprende_hotel"    => $data[3] ?? null,
                    "quota_non_comprende_hotel"=> $data[4] ?? null,
                    "file_fornitore_hotel"     => (!empty($data[5]) && $data[5] !== "NULL")
                        ? $data[5]
                        : json_encode([]),
                    "note" => $data[6] ?? null,
                ]);
            } catch (\Exception $e) {
                Log::error("Errore HotelPreventive → " . json_encode($data) . " → " . $e->getMessage());
            }
        }

        fclose($csvFile);
    }
}
