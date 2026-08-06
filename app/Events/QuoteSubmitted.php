<?php

namespace App\Events;

use App\Models\Quote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Quote $quote) {}
}
