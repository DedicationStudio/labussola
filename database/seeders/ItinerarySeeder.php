<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Itinerary;

class ItinerarySeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = fopen(base_path("database/seeders/csv/itineraries.csv"), "r");

        // Se c’è header, salto la prima riga
        //$header = fgetcsv($csvFile, 0, ";");

        while (($data = fgetcsv($csvFile, 0, ";")) !== false) {
            // Struttura CSV: [0] id, [1] nome, [2] html
            $nome = $data[1];
            $html = $data[2];


            Itinerary::create([
                "nome" => $nome,
                "itinerario" => [
                    [
                        "titolo" => "Itinerario",   // oppure ricavato da $data[x]
                        "descrizione" => $html,
                    ]
                ],
            ]);

        }

        fclose($csvFile);
    }
}
