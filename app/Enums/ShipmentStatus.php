<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case DriverAssigned = 'driver_assigned';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * The allowed next statuses for each status. Any transition not listed
     * here is illegal and must be rejected by the Actions layer.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published, self::Cancelled],
            self::Published => [self::Quoted, self::Cancelled, self::Expired],
            self::Quoted => [self::Accepted, self::Cancelled, self::Expired],
            self::Accepted => [self::DriverAssigned, self::Cancelled],
            self::DriverAssigned => [self::PickedUp, self::Cancelled],
            self::PickedUp => [self::InTransit],
            self::InTransit => [self::Delivered],
            self::Delivered => [self::Completed],
            self::Completed, self::Cancelled, self::Expired => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Statuses where the shipment is open to receive quotes.
     */
    public function isOpenForQuotes(): bool
    {
        return in_array($this, [self::Published, self::Quoted], true);
    }
}
