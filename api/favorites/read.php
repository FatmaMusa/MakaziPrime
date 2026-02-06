<?php
// api/favorites/read.php - Get user's favorite properties
header("Content-Type: application/json");
session_start();

include_once __DIR__ . '/../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Please login to view favorites"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

try {
    $query = "SELECT p.*, pt.name as type_name, f.created_at as favorited_at
              FROM favorites f
              JOIN properties p ON f.property_id = p.property_id
              LEFT JOIN property_types pt ON p.type_id = pt.type_id
              WHERE f.user_id = :user_id
              ORDER BY f.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
