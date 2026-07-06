<?php

return [
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'client_id' => env('MQTT_CLIENT_ID', 'ruangas-listener'),
    'topic' => env('MQTT_TOPIC', 'tracker/#'),
    'qos' => (int) env('MQTT_QOS', 1),
    'tls' => filter_var(env('MQTT_TLS', false), FILTER_VALIDATE_BOOLEAN),
    'connect_timeout' => (int) env('MQTT_CONNECT_TIMEOUT', 10),
    'socket_timeout' => (int) env('MQTT_SOCKET_TIMEOUT', 5),
    'keep_alive_interval' => (int) env('MQTT_KEEP_ALIVE_INTERVAL', 60),
];
