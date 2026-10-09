<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Journey;
use App\Models\Ticket;
use App\Models\TripStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketService
{
    public function __construct(private TrainSearchService $trains, private PaymentService $payments) {}

    /**
     * Create an unpaid ticket for one search result (trip + from/to stops + date).
     * The price always comes from the GTFS fare tables, never from the client.
     *
     * Clicking "Buy" again for the same train returns the unpaid ticket that already exists
     * ($reused = true) instead of creating a copy. Each ticket is for one passenger.
     */
    public function purchase(User $user, string $tripId, string $fromStopId, string $toStopId, CarbonInterface $date, ?bool &$reused = null): Ticket
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

        $departure = $this->gtfsTimeOnDate($date, $trip->departure_time);
        if ($departure->isPast()) {
            throw new BookingException('This train has already left.');
        }

        // One row per real journey; the unique key + createOrFirst make simultaneous purchases share it.
        $journey = Journey::createOrFirst([
            'trip_id' => $trip->trip_id,
            'from_stop_id' => $trip->from_stop_id,
            'to_stop_id' => $trip->to_stop_id,
            'departure_time' => $departure,
        ], [
            'arrival_time' => $this->gtfsTimeOnDate($date, $trip->arrival_time),
        ]);

        $existing = $user->tickets()->where('journey_id', $journey->id)->where('status', Ticket::PENDING)->first();
        if ($reused = (bool) $existing) {
            return $existing;
        }

        return $this->createWithUniqueCode(fn (string $code) => $user->tickets()->create([
            'journey_id' => $journey->id,
            'ticket_code' => $code,
            'price' => $trip->price,
            'currency' => $trip->currency,
            'status' => Ticket::PENDING,
            'purchased_at' => now(),
        ]));
    }

    /**
     * Only unpaid tickets can be cancelled (paid ones are refunded through their payment).
     * An unfinished payment is cancelled with it, so it can no longer make the ticket "paid".
     */
    public function cancel(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticket->id);

            if ($ticket->status !== Ticket::PENDING) {
                throw new BookingException('Only unpaid tickets can be cancelled. Request a refund for paid tickets.');
            }

            $this->payments->cancelPendingPaymentsOf($ticket);
            $ticket->moveTo(Ticket::CANCELLED);

            return $ticket;
        });
    }

    /**
     * Unpaid tickets whose train has left can never be used: cancel them (scheduled every few minutes).
     *
     * @return int how many were cancelled
     */
    public function cancelUnpaidDeparted(): int
    {
        $tickets = Ticket::where('status', Ticket::PENDING)
            ->whereHas('journey', fn ($journey) => $journey->where('departure_time', '<', now()))
            ->get();

        $tickets->each(function (Ticket $ticket) {
            try {
                $this->cancel($ticket);
            } catch (BookingException) {
                // paid in the meantime: nothing to do
            }
        });

        return $tickets->count();
    }

    /**
     * GTFS times can go past midnight ("25:10:00" = 01:10 the next day).
     */
    private function gtfsTimeOnDate(CarbonInterface $date, string $time): Carbon
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $time));

        return Carbon::parse($date)->startOfDay()->addSeconds($hours * 3600 + $minutes * 60 + $seconds);
    }

    /**
     * Random 10-character code. ticket_code is unique in the database; if two requests ever
     * pick the same code at the same moment, the loser simply tries a new one.
     */
    private function createWithUniqueCode(callable $create): Ticket
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $create(strtoupper(Str::random(10)));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }
}
