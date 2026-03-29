<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Hotel::truncate();
        $csvFile = fopen(base_path("database/seeders/csv/users.csv"), "r");

        while (($data = fgetcsv($csvFile, 2000, ";")) !== FALSE) {


            \App\Models\User::create([
                "role_id" => (!empty($data[1]) && $data[1] !== "NULL") ? intval($data[1]) : null,
                "nome" => $data[2],
                "cognome" => $data[3],
                "email" => $data[4],
                "email_verified_at" => (!empty($data[5]) && $data[5] !== "NULL") ? $data[5] : null,
                'password' => $data[6],
                "telefono" => $data[7],
                "n_preventivi_gestibili" => intval($data[8]),
                "first_change_password" => (!empty($data[9]) && $data[9] !== "NULL")
                    ? (intval($data[9]) === 1 ? 1 : 0)
                    : 0,
                "fcm_token" => $data[10],
                "remember_token" => $data[11],
                "created_at" => (!empty($data[12]) && $data[12] !== "NULL") ? $data[12] : now(),
                "updated_at" => (!empty($data[13]) && $data[13] !== "NULL") ? $data[13] : now(),
            ]);

        }
        fclose($csvFile);

    }
}
