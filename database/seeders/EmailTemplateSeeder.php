<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Hotel::truncate();
        $csvFile = fopen(base_path("database/seeders/csv/template_email.csv"), "r");

        while (($data = fgetcsv($csvFile, 2000, ";")) !== FALSE) {


            \App\Models\EmailTemplate::create([
                "nome" => $data['1'],
                "corpo_email" => $data['2'],
            ]);

        }
        fclose($csvFile);

    }
}
