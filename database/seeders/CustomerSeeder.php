<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Hotel::truncate();
        $csvFile = fopen(base_path("database/seeders/csv/customers.csv"), "r");

        while (($data = fgetcsv($csvFile, 2000, ";")) !== FALSE) {

            $tipo = $data[1] ?? null;
            if (!in_array($tipo, ['azienda', 'privato', 'scuola'])) {
                $tipo = 'privato'; // fallback
            }
            \App\Models\Customer::create([
                "tipo_cliente" => $tipo,
                "nome" => $data['2'],
                "cognome" => $data['3'],
                "ragione_sociale" => $data['4'],
                "piva_cf" => $data['5'],
                "indirizzo" => $data['6'],
                "citta" => $data['7'],
                "cap" => $data['8'],
                "provincia" => $data['9'],
                "stato" => $data['10'],
                "email" => $data['11'],
                "telefono" => $data['12'],
            ]);

        }
    }
}
