<?php
/**
 * KC-TEC Check-In PHP Backend for MySQL
 * Place this file on your Namecheap server (e.g., public_html/api.php)
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// --- 1. DATABASE CONFIGURATION ---
// Create a database in Namecheap cPanel > MySQL Databases and enter details here
$db_host = 'localhost';
$db_name = 'sremjomt_kctec_db';
$db_user = 'sremjomt_admin';
$db_pass = 'M#7og9ntxe';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // Handle preflight requests
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
    exit;
}

// --- 2. PROCESS REQUEST ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["status" => "error", "message" => "No action specified"]);
    exit;
}

$action = $data['action'];

if ($action === 'log_entry') {
    handleLogEntry($pdo, $data);
} elseif ($action === 'leave_request') {
    handleLeaveRequest($pdo, $data);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

// --- 3. LOGIC FUNCTIONS ---

function handleLogEntry($pdo, $data) {
    try {
        $stmt = $pdo->prepare("INSERT INTO CheckInLogs (date, time, staffId, staffName, storeId, sessionType, dist, deviceId) 
                               VALUES (CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['staffId'],
            $data['staffName'],
            $data['storeId'],
            $data['type'],
            $data['dist'],
            $data['deviceId']
        ]);

        echo json_encode(["status" => "success", "message" => "Session logged"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}

function handleLeaveRequest($pdo, $data) {
    try {
        $stmt = $pdo->prepare("INSERT INTO ExceptionsAndLeave (staffId, staffName, category, startDate, endDate, reason) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['staffId'],
            $data['staffName'],
            $data['type'],
            $data['start'],
            $data['end'],
            $data['reason']
        ]);

        echo json_encode(["status" => "success", "message" => "Exception recorded"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>