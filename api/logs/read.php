<?php
// api/logs/read.php
header("Content-Type: application/json");
session_start();
include_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Basic filters (optional)
$admin_id = isset($_GET['admin_id']) ? $_GET['admin_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;

$query = "SELECT l.*, u.full_name as admin_name 
          FROM admin_logs l 
          JOIN users u ON l.admin_id = u.user_id 
          WHERE 1=1";

if ($admin_id) {
    $query .= " AND l.admin_id = :admin_id";
}
if ($date) {
    $query .= " AND DATE(l.log_date) = :date";
}

$query .= " ORDER BY l.log_date DESC LIMIT 100";

$stmt = $db->prepare($query);

if ($admin_id)
    $stmt->bindParam(":admin_id", $admin_id);
if ($date)
    $stmt->bindParam(":date", $date);

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($logs);
?>