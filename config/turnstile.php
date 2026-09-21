<?php
return [
    'sitekey' => (string) env('TURNSTILE_SITEKEY', ''),
    'secret' => (string) env('TURNSTILE_SECRET', ''),
];