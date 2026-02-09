<?php
// api/users/delete.php - Admin delete user
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

if (empty($data->user_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing user ID"]);
    exit;
}

$user_id = (int) $data->user_id;

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(["message" => "You cannot delete your own account while logged in."]);
    exit;
}

try {
    // Cascading deletes (FOREIGN KEY constraints) in database.sql should handle viewings, bookings, etc.
    // However, some might not have ON DELETE CASCADE if not specified.

    $query = "DELETE FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "User deleted successfully"]);
    } else {
        echo json_encode(["message" => "Failed to delete user"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>