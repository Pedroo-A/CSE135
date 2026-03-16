<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

$host = 'localhost';
$db   = 'analytics_db';
$user = 'collector_bot';
$pass = 'Pedro3344752';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    http_response_code(500);
    exit(json_encode(["error" => "Database conn failed"]));
}

// Get HTTP Method and id if passed
$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['request']) ? trim($_GET['request'], '/') : null;
$id = (is_numeric($request)) ? intval($request) : null;

// Route request based on HTTP Method
switch ($method) {
    case 'GET':
        if ($id) {
            // GET /api/metrics/id
            $stmt = $pdo->prepare("SELECT * FROM raw_metrics WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // GET /api/metrics
            $stmt = $pdo->query("SELECT * FROM raw_metrics");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($result) {
            if ($id) {
                $result['payload'] = json_decode($result['payload']);
            } else {
                foreach ($result as &$row) {
                    $row['payload'] = json_decode($row['payload']);
                }
            }
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No records found"]);
        }
        break;

    case 'POST':
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if ($data) {
            $sessionId = isset($data['sessionId']) ? $data['sessionId'] : 'api_manual_insert';
            //insert into database
            $stmt = $pdo->prepare("INSERT INTO raw_metrics (session_id, payload) VALUES (?, ?)");
            try {
                $stmt->execute([$sessionId, $json]);
                $newId = $pdo->lastInsertId(); //get id from new row
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Record created", "id" => $newId]);
            } catch (\PDOException $e) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Failed to insert data"]);
            }
        } else {
            // Handle bad JSON
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid or no JSON data received"]);
        }
        break;

    case 'DELETE':
        //THIS NEEDS AN ID, YOU WILL NOT DELETE WITHOUT AN ID
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM raw_metrics WHERE id = ?");
                $stmt->execute([$id]);
                
                //ensure it was actually deleted
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(["status" => "success", "message" => "Record deleted"]);
                } else {
                    //given id doesnt exist
                    http_response_code(404);
                    echo json_encode(["status" => "error", "message" => "Record not found"]);
                }
            } catch (\PDOException $e) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Failed to delete"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "You need an ID to delete data"]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Incorrect Method"]);
        break;
}
?>
