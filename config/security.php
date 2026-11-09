<?php
return [
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        'Strict-Transport-Security' => 'max-age=31536000',
    ],
    'csp' => [
        'default-src' => env('CSP_DEFAULT_SRC', "'self' https:"),
        'script-src' => env('CSP_SCRIPT_SRC', "'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://challenges.cloudflare.com 'unsafe-inline'"),
        'style-src' => env('CSP_STYLE_SRC', "'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'"),
        'font-src' => env('CSP_FONT_SRC', "https://fonts.gstatic.com https://cdn.jsdelivr.net"),
        'img-src' => env('CSP_IMG_SRC', "'self' data: https://www.gravatar.com https://upload.wikimedia.org"),
    ],
];
