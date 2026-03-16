<?php
header('Content-Type: text/plain');

$method = $_SERVER['REQUEST_METHOD'];
$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$time = date('Y-m-d H:i:s');
$hostname = gethostname();

echo "PHP Echo Endpoint\n";
echo "Hostname: $hostname\n";
echo "Date/Time: $time\n";
echo "Method: $method\n";
echo "User IP: $ip\n";
echo "User Agent: $userAgent\n\n";


$data = [];

if ($method === 'GET' || $method === 'DELETE') {
    $data = $_GET;
} else {
    // Check if input is jspon
    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
    
    if (stripos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
    } else {
        // POST/PUT handling
        if ($method === 'POST') {
            $data = $_POST;
        } else {
            parse_str(file_get_contents('php://input'), $data);
        }
    }
}

echo " Received Data:\n";
if (empty($data)) {
    echo "No data received.\n";
} else {
    print_r($data);
}
?>