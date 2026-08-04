<?php

namespace App\Actions\Ratings;

use App\Enums\ShipmentStatus;
use App\Exceptions\ShipmentException;
use App\Models\Rating;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RateShipmentAction
{
    /**
     * Rate the counterparty of a completed shipment. Customers rate the
     * assigned transporter's user and vice versa; transporter profile
     * aggregates are recomputed after each rating.
     */
    public function execute(User $rater, Shipment $shipment, int $score, ?string $comment = null): Rating
    {
        if ($shipment->status !== ShipmentStatus::Completed) {
            throw ShipmentException::notRateable();
        }

        $ratee = $this->rateeFor($rater, $shipment);

        return DB::transaction(function () use ($rater, $ratee, $shipment, $score, $comment) {
            $alreadyRated = $shipment->ratings()
                ->where('rater_id', $rater->id)
                ->exists();

            if ($alreadyRated) {
                throw ShipmentException::alreadyRated();
            }

            $rating = $shipment->ratings()->create([
                'rater_id' => $rater->id,
                'ratee_id' => $ratee->id,
                'score' => $score,
                'comment' => $comment,
            ]);

            $this->refreshTransporterAggregates($ratee);

            return $rating;
        });
    }

    private function rateeFor(User $rater, Shipment $shipment): User
    {
        $shipment->loadMissing('assignedTransporter.user');

        $transporterUser = $shipment->assignedTransporter?->user;

        if ($rater->id === $shipment->customer_id && $transporterUser !== null) {
            return $transporterUser;
        }

        if ($transporterUser !== null && $rater->id === $transporterUser->id) {
            return $shipment->customer;
        }

        throw ShipmentException::notParticipant();
    }

    private function refreshTransporterAggregates(User $ratee): void
    {
        $profile = $ratee->transporterProfile;

        if ($profile === null) {
            return;
        }

        $received = Rating::query()->where('ratee_id', $ratee->id);

        $profile->forceFill([
            'rating_avg' => round((float) $received->avg('score'), 2),
            'rating_count' => $received->count(),
        ])->save();
    }
}
