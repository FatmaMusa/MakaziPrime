<?php
// api/bookings/delete.php - Admin delete booking
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

if (empty($data->booking_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing booking ID"]);
    exit;
}

$booking_id = (int) $data->booking_id;

try {
    $db->beginTransaction();

    // Cascading delete should handle booking_details, but let's be explicit if needed.
    // However, the database.sql shows ON DELETE CASCADE for booking_details.

    $query = "DELETE FROM bookings WHERE booking_id = :booking_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':booking_id', $booking_id);

    if ($stmt->execute()) {
        $db->commit();
        echo json_encode(["message" => "Booking deleted successfully"]);
    } else {
        $db->rollBack();
        echo json_encode(["message" => "Failed to delete booking"]);
    }
} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>