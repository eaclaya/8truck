<?php

return [
    'action' => 'View details',
    'new_load' => [
        'title' => 'New load available',
        'body' => ':origin → :destination · pickup :date',
    ],
    'quote_received' => [
        'title' => 'New quote',
        'body' => ':transporter quoted :amount for :origin → :destination',
    ],
    'quote_accepted' => [
        'title' => 'Quote accepted!',
        'body' => 'Your :amount quote for :origin → :destination was accepted',
    ],
    'status_updated' => [
        'title' => 'Shipment updated',
        'body' => ':origin → :destination is now: :status',
    ],
    'completed' => [
        'title' => 'Shipment completed',
        'body' => 'The customer confirmed delivery of :origin → :destination',
    ],
    'rating_received' => [
        'title' => 'New rating',
        'body' => ':rater rated you :score stars',
    ],
];
