<?php

namespace Database\Seeders;

use App\Models\TypeRequest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeRequest::create(['nome' => 'Settimana Bianca']);
        TypeRequest::create(['nome' => 'Gita Scolastica']);
        TypeRequest::create(['nome' => 'Vacanza Estiva']);
    }
}
