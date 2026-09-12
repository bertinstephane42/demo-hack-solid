<?php
return [
    'max' => (int) env('RATE_LIMIT_MAX', 30),
    'window' => (int) env('RATE_LIMIT_WINDOW', 60),
];