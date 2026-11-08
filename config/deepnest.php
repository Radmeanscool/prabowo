<?php

return [
    // Content-Security-Policy ketat (matikan hanya saat pengembangan dengan Vite dev server).
    'csp' => (bool) env('DEEPNEST_CSP', true),

    // Paksa URL https (berguna di hosting di balik proxy/CDN).
    'force_https' => (bool) env('FORCE_HTTPS', false),

    // Token wizard /setup untuk membuat admin pertama tanpa SSH. Kosongkan setelah dipakai.
    'setup_token' => env('SETUP_TOKEN'),
];
