<?php
// api/viewings/read_admin.php - Get all viewing appointments for admin
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

try {
    $query = "SELECT vs.*, p.title as property_title, p.location, u.full_name as user_name, u.email as user_email
              FROM viewing_slots vs
              JOIN properties p ON vs.property_id = p.property_id
              JOIN users u ON vs.user_id = u.user_id
              ORDER BY vs.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $viewings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($viewings);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>