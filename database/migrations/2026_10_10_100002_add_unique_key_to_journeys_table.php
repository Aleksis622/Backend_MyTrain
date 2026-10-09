<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per real journey (trip + from + to + departure), so tickets bought at the same
     * moment share it instead of creating copies.
     */
    public function up(): void
    {
        // Merge existing copies into the oldest row first.
        $copies = DB::table('journeys')
            ->select('trip_id', 'from_stop_id', 'to_stop_id', 'departure_time', DB::raw('MIN(id) AS keep_id'))
            ->groupBy('trip_id', 'from_stop_id', 'to_stop_id', 'departure_time')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($copies as $copy) {
            $duplicateIds = DB::table('journeys')
                ->where('trip_id', $copy->trip_id)
                ->where('from_stop_id', $copy->from_stop_id)
                ->where('to_stop_id', $copy->to_stop_id)
                ->where('departure_time', $copy->departure_time)
                ->where('id', '!=', $copy->keep_id)
                ->pluck('id');

            DB::table('tickets')->whereIn('journey_id', $duplicateIds)->update(['journey_id' => $copy->keep_id]);
            DB::table('journeys')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('journeys', function (Blueprint $table) {
            $table->unique(['trip_id', 'from_stop_id', 'to_stop_id', 'departure_time'], 'journeys_unique_trip_leg');
        });
    }

    public function down(): void
    {
        Schema::table('journeys', function (Blueprint $table) {
            $table->dropUnique('journeys_unique_trip_leg');
        });
    }
};
