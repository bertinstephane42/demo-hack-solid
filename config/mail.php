<?php
return [
    'from' => env('MAIL_FROM', 'contact@cours-reseaux.fr'),
    'to' => env('MAIL_TO', 'contact@cours-reseaux.fr'),
    'from_name' => 'Cours-Reseaux',
    'log_enabled' => (bool) env('MAIL_LOG_ENABLED', false),
];
