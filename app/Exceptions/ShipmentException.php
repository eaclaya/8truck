<?php

namespace App\Exceptions;

use App\Enums\ShipmentStatus;
use DomainException;

class ShipmentException extends DomainException
{
    public static function invalidTransition(ShipmentStatus $from, ShipmentStatus $to): self
    {
        return new self(__('marketplace.invalid_transition', ['from' => $from->value, 'to' => $to->value]));
    }

    public static function notOpenForQuotes(ShipmentStatus $status): self
    {
        return new self(__('marketplace.not_open_for_quotes', ['status' => $status->value]));
    }

    public static function alreadyQuoted(): self
    {
        return new self(__('marketplace.already_quoted'));
    }

    public static function ownShipment(): self
    {
        return new self(__('marketplace.own_shipment'));
    }

    public static function quoteNotPending(): self
    {
        return new self(__('marketplace.quote_not_pending'));
    }

    public static function notShipmentOwner(): self
    {
        return new self(__('marketplace.not_shipment_owner'));
    }
}
