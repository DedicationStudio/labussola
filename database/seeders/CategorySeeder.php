<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'CULTURA E PATRIMONIO',
            'SOGGIORNI LINGUISTICI ALL’ESTERO',
            'ATTIVITÀ RICREATIVE PER GRUPPI',
            'RISTORAZIONE ED ENOGASTRONOMIA',
            'ATTIVITÀ NATURALISTICHE E SPORTIVE',
            'BENESSERE E RELAX',
            'SERVIZI DI SUPPORTO',
            'NAVIGAZIONE E TRASPORTI',
            'SPORT INVERNALI E MONTAGNA',
        ];

        foreach ($categories as $category) {
            DB::table('category_extra_services')->insert([
                'nome'       => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
