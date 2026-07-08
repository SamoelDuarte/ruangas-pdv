<?php

return [
    'host' => env('TRACKER_TCP_HOST', '0.0.0.0'),
    'port' => (int) env('TRACKER_TCP_PORT', 5001),
    'client_timeout' => (int) env('TRACKER_TCP_CLIENT_TIMEOUT', 30),
    'max_bytes_per_read' => (int) env('TRACKER_TCP_MAX_BYTES_PER_READ', 4096),
    'save_messages' => (bool) env('TRACKER_TCP_SAVE_MESSAGES', true),
    'reverse_geocode' => (bool) env('TRACKER_TCP_REVERSE_GEOCODE', true),
];
