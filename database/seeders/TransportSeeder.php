<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Hotel::truncate();
        $csvFile = fopen(base_path("database/seeders/csv/transports.csv"), "r");

        while (($data = fgetcsv($csvFile, 2000, ";")) !== false) {
            \App\Models\Transport::create([
                "supplier_id" => (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null,
                "nome" => $data[2] ?? null,
                "descrizione" => $data[3] ?? null,
                "foto" => $data[4] ?? null,
            ]);
        }
        fclose($csvFile);

    }
}
