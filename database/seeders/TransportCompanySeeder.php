<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransportCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Hotel::truncate();
        $csvFile = fopen(base_path("database/seeders/csv/transport_companies.csv"), "r");

        while (($data = fgetcsv($csvFile, 2000, ";")) !== FALSE) {


            \App\Models\TransportCompany::create([
                "nome" => $data['1'],
                "immagine" => $data['2'],
            ]);

        }
        fclose($csvFile);

    }
}
