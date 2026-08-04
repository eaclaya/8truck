<?php

namespace App\Exceptions;

use App\Enums\ShipmentStatus;
use DomainException;

class ShipmentException extends DomainException
{
    public static function invalidTransition(ShipmentStatus $from, ShipmentStatus $to): self
    {
        return new self("A shipment cannot transition from [{$from->value}] to [{$to->value}].");
    }

    public static function notOpenForQuotes(ShipmentStatus $status): self
    {
        return new self("Quotes cannot be submitted while the shipment is [{$status->value}].");
    }

    public static function alreadyQuoted(): self
    {
        return new self('This transporter has already submitted a quote for the shipment.');
    }

    public static function ownShipment(): self
    {
        return new self('A transporter cannot quote their own shipment.');
    }

    public static function quoteNotPending(): self
    {
        return new self('The quote is no longer pending and cannot be accepted.');
    }

    public static function notShipmentOwner(): self
    {
        return new self('Only the shipment owner can perform this action.');
    }
}
