<?php
// api/viewings/delete.php - Admin delete viewing appointment
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

if (empty($data->slot_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing slot ID"]);
    exit;
}

$slot_id = (int) $data->slot_id;

try {
    $query = "DELETE FROM viewing_slots WHERE slot_id = :slot_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':slot_id', $slot_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Viewing appointment deleted successfully"]);
    } else {
        echo json_encode(["message" => "Failed to delete viewing appointment"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>