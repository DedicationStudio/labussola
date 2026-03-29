<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class HotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Hotel::truncate();
        $csvFile = fopen(base_path("database/seeders/csv/hotels.csv"), "r");

        while (($data = fgetcsv($csvFile, 2000, ";")) !== false) {
            \App\Models\Hotel::create([
                "supplier_id" => (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null,
                "nome" => $data[2] ?? null,
                "indirizzo" => $data[3] ?? null,
                "descrizione" => $data[4] ?? null,
                "foto" => $data[5] ?? null,
                "stelle" => intval($data[6] ?? 0),
            ]);
        }
        fclose($csvFile);

    }
}