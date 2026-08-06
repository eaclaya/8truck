<?php

return [
    'action' => 'Ver detalles',
    'new_load' => [
        'title' => 'Nueva carga disponible',
        'body' => ':origin → :destination · recogida :date',
    ],
    'quote_received' => [
        'title' => 'Nueva cotización',
        'body' => ':transporter cotizó :amount por :origin → :destination',
    ],
    'quote_accepted' => [
        'title' => '¡Cotización aceptada!',
        'body' => 'Tu cotización de :amount para :origin → :destination fue aceptada',
    ],
    'status_updated' => [
        'title' => 'Envío actualizado',
        'body' => ':origin → :destination ahora está: :status',
    ],
    'completed' => [
        'title' => 'Envío completado',
        'body' => 'El cliente confirmó la entrega de :origin → :destination',
    ],
    'rating_received' => [
        'title' => 'Nueva calificación',
        'body' => ':rater te calificó con :score estrellas',
    ],
];
