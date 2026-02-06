<?php
// api/bookings/update_status.php
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

$data = json_decode(file_get_contents("php://input"));

if (empty($data->booking_id) || empty($data->status)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing ID or Status."]);
    exit();
}

$valid_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($data->status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid status."]);
    exit();
}

$query = "UPDATE bookings SET status = :status WHERE booking_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":status", $data->status);
$stmt->bindParam(":id", $data->booking_id);

if ($stmt->execute()) {
    // Log
    $log_q = "INSERT INTO admin_logs (admin_id, action) VALUES (:admin_id, :action)";
    $log_stmt = $db->prepare($log_q);
    $action = "Updated Booking #$data->booking_id to $data->status";
    $log_stmt->bindParam(":admin_id", $_SESSION['user_id']);
    $log_stmt->bindParam(":action", $action);
    $log_stmt->execute();

    http_response_code(200);
    echo json_encode(["message" => "Booking updated successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database error."]);
}
?>