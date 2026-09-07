<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;

class AgencySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('agencies')->delete();

        Agency::create([
            'agency_id' => 'vivi',
            'agency_name' => 'Vivi',
            'agency_url' => 'https://www.vivi.lv',
            'agency_timezone' => 'Europe/Riga',
            'agency_lang' => 'lv',
            'agency_phone' => '80007600',
            'agency_fare_url' => null,
            'agency_email' => 'vilciens@info.vivi.lv',
        ]);
    }
}
