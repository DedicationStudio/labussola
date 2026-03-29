<?php

namespace Database\Seeders;

use App\Models\TypeRequest;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(TypeRequestSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(HotelSeeder::class);
        $this->call(TransportCompanySeeder::class);
        $this->call(EmailTemplateSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(ItinerarySeeder::class);
        $this->call(TransportSeeder::class);
        $this->call(PreventiveSeeder::class);
        $this->call(ExtraServiceSeeder::class);
        $this->call(HotelPreventiveSeeder::class);
        $this->call(PreventiveTransportSeeder::class);
        $this->call(HotelPreventiveRoomSeeder::class);
        $this->call(PreventiveExtraServiceSeeder::class);
    }


}
