<?php
// api/viewings/update_status.php - Admin update viewing status
header("Content-Type: application/json");
session_start();
include_once __DIR__ . '/../../config/db.php';

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->slot_id) || empty($data->status)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required fields"]);
    exit;
}

$slot_id = (int) $data->slot_id;
$status = $data->status;

// Validate status
$allowed_statuses = ['pending', 'confirmed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid status"]);
    exit;
}

try {
    $query = "UPDATE viewing_slots SET status = :status WHERE slot_id = :slot_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':slot_id', $slot_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Viewing status updated successfully"]);
    } else {
        echo json_encode(["message" => "Failed to update status"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>