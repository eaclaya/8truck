<?php

return [
    'invalid_transition' => 'A shipment cannot transition from :from to :to.',
    'not_open_for_quotes' => 'Quotes cannot be submitted while the shipment is :status.',
    'already_quoted' => 'This transporter has already submitted a quote for this shipment.',
    'own_shipment' => 'A transporter cannot quote their own shipment.',
    'quote_not_pending' => 'The quote is no longer pending and cannot be accepted.',
    'not_shipment_owner' => 'Only the shipment owner can perform this action.',
];
