<?php

namespace Database\Seeders;

use App\Models\Train;
use Illuminate\Database\Seeder;

class TrainSeeder extends Seeder
{
    /**
     * A few trains so the live map and train.tracker endpoint have something to move.
     */
    public function run(): void
    {
        $trains = [
            ['number' => 'VIVI-101', 'name' => 'Rīga - Jelgava', 'type' => 'electric'],
            ['number' => 'VIVI-202', 'name' => 'Rīga - Sigulda', 'type' => 'electric'],
            ['number' => 'VIVI-303', 'name' => 'Rīga - Daugavpils', 'type' => 'diesel'],
            ['number' => 'VIVI-404', 'name' => 'Rīga - Tukums', 'type' => 'electric'],
            ['number' => 'VIVI-505', 'name' => 'Rīga - Aizkraukle', 'type' => 'electric'],
        ];

        foreach ($trains as $train) {
            Train::updateOrCreate(['number' => $train['number']], [...$train, 'operator' => 'Vivi']);
        }
    }
}
