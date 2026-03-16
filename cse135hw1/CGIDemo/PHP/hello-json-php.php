<?php
header('Content-Type: application/json');

$response = [
    "team" => "Pedro",
    "language" => "PHP",
    "timestamp" => date('Y-m-d H:i:s'),
    "ip_address" => $_SERVER['REMOTE_ADDR'],
    "message" => "Hello from the JSON"
];

echo json_encode($response);
?>