<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Hotel::truncate();
        $csvFile = fopen(base_path("database/seeders/csv/fornitores.csv"), "r");

        while (($data = fgetcsv($csvFile, 2000, ";")) !== FALSE) {


            \App\Models\Supplier::create([
                "reliability_id" => (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null,
                "nome" => $data['2'],
                "cognome" => $data['3'],
                "ragione_sociale" => $data['4'],
                "piva_cf" => $data['5'],
                "codice_fiscale" => !empty($data[6]) && $data[6] !== "NULL" ? $data[6] : null,
                "indirizzo" => $data['7'],
                "email" => !empty($data[8]) ? json_encode([$data[8]]) : json_encode([]),
                "telefono" => !empty($data[9]) ? json_encode([$data[9]]) : json_encode([]),
                "sito_web" => !empty($data[10]) ? json_encode([$data[10]]) : json_encode([]),
                "portale_web" => !empty($data[11]) ? json_encode([$data[11]]) : json_encode([]),
                "regione" => $data['12'],
                "stato" => $data['13'],
                "citta" => $data['14'],
                "cap" => $data['15'],
                "provincia" => $data['16'],
                "descrizione" => $data['17'],
                "allegati" => (!empty($data[18]) && $data[18] !== "NULL")
                     ? json_encode([$data[18]])
                    : json_encode([]),
                "note" => $data['19'],
            ]);

        }
        fclose($csvFile);

    }
}
