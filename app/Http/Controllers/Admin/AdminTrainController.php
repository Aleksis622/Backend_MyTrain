<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Trip;
use App\Models\TripStatus;
use App\Services\TripStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Train status (delays / cancellations) per day. The timetable itself is read-only:
 * trips, stop times and calendars always stay as the seeders loaded them.
 */
class AdminTrainController extends Controller
{
    /**
     * GET /admin/trains?date=2026-10-07 -> all trips of that day with their status.
     */
    public function index(Request $request, TripStatusService $statuses): JsonResponse
    {
        $data = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = Carbon::parse($data['date'] ?? today());

        return response()->json([
            'date' => $date->toDateString(),
            'trips' => $statuses->tripsOn($date),
        ]);
    }

    /**
     * PUT /admin/trains/{trip}/status  { date, status, minutes?, reason? }
     */
    public function updateStatus(Request $request, string $tripId, TripStatusService $statuses): JsonResponse
    {
        $trip = Trip::findOrFail($tripId);

        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'status' => ['required', Rule::in([TripStatus::ON_TIME, TripStatus::DELAYED, TripStatus::CANCELLED])],
            'minutes' => 'required_if:status,delayed|nullable|integer|min:1|max:600',
            'reason' => 'nullable|string|max:255',
        ]);

        $tripStatus = $statuses->set(
            $trip->trip_id,
            Carbon::parse($data['date']),
            $data['status'],
            (int) ($data['minutes'] ?? 0),
            $data['reason'] ?? null,
        );

        AdminAction::record($request->user(), AdminAction::TRAIN_STATUS_CHANGED, 'train', $trip->trip_id, [
            'train' => $trip->route?->route_short_name ?: $trip->trip_id,
            'headsign' => $trip->trip_headsign,
            'date' => $data['date'],
            'status' => $data['status'],
            'minutes' => $tripStatus?->delay_minutes,
            'reason' => $tripStatus?->reason,
        ]);

        return response()->json([
            'message' => 'Status updated',
            'status' => $tripStatus?->status ?? TripStatus::ON_TIME,
            'delay_minutes' => $tripStatus?->delay_minutes ?? 0,
            'status_reason' => $tripStatus?->reason,
        ]);
    }
}
