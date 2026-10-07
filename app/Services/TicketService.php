<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Journey;
use App\Models\Ticket;
use App\Models\TripStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TicketService
{
    public function __construct(private TrainSearchService $trains) {}

    /**
     * Create an unpaid ticket for one search result (trip + from/to stops + date).
     * The price always comes from the GTFS fare tables, never from the client.
     */
    public function purchase(User $user, string $tripId, string $fromStopId, string $toStopId, CarbonInterface $date): Ticket
    {
        $trip = $this->trains->search(collect([$fromStopId]), collect([$toStopId]), $date, $tripId, 1)->first();

        if (! $trip) {
            throw new BookingException('This train does not run between these stations on the selected date.');
        }

        if ($trip->status === TripStatus::CANCELLED) {
            throw new BookingException('This train is cancelled on the selected date.');
        }

        if ($trip->price === null) {
            throw new BookingException('No fare is available for this connection.');
        }

        $journey = Journey::firstOrCreate([
            'trip_id' => $trip->trip_id,
            'from_stop_id' => $trip->from_stop_id,
            'to_stop_id' => $trip->to_stop_id,
            'departure_time' => $this->gtfsTimeOnDate($date, $trip->departure_time),
        ], [
            'arrival_time' => $this->gtfsTimeOnDate($date, $trip->arrival_time),
        ]);

        return $user->tickets()->create([
            'journey_id' => $journey->id,
            'ticket_code' => $this->uniqueTicketCode(),
            'price' => $trip->price,
            'currency' => $trip->currency,
            'status' => 'pending',
            'purchased_at' => now(),
        ]);
    }

    /**
     * Only unpaid tickets can be cancelled; paid ones go through a payment refund.
     */
    public function cancel(Ticket $ticket): Ticket
    {
        if ($ticket->status !== 'pending') {
            throw new BookingException('Only unpaid tickets can be cancelled. Request a refund for paid tickets.');
        }

        $ticket->update(['status' => 'cancelled']);

        return $ticket;
    }

    /**
     * GTFS times can go past midnight ("25:10:00" = 01:10 the next day).
     */
    private function gtfsTimeOnDate(CarbonInterface $date, string $time): Carbon
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $time));

        return Carbon::parse($date)->startOfDay()->addSeconds($hours * 3600 + $minutes * 60 + $seconds);
    }

    private function uniqueTicketCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (Ticket::where('ticket_code', $code)->exists());

        return $code;
    }
}
