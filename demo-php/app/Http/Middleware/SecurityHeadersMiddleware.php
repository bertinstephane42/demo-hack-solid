<?php

namespace App\Http\Middleware;

use Core\Request;
use Core\Response;

class SecurityHeadersMiddleware implements Middleware
{
    public function handle(Request $request): ?Response
    {
        $security = config('security', []);
        $headers = $security['headers'] ?? [];
        $csp = $security['csp'] ?? [];

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if (!empty($csp)) {
            $cspString = '';
            foreach ($csp as $directive => $value) {
                $cspString .= "{$directive} {$value}; ";
            }
            header("Content-Security-Policy: {$cspString}");
        }

        return null;
    }
}
