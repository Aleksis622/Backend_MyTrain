<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
{
    $this->call([
        AgencySeeder::class,
        RoutesSeeder::class,
        StopsSeeder::class,
        TripsSeeder::class,
        StopTimesSeeder::class,
        CalendarSeeder::class,
        CalendarDatesSeeder::class,
        FareAttributesSeeder::class,
        FareRulesSeeder::class,
    ]);
}

}
