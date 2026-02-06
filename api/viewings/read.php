<?php
// api/viewings/read.php - Get user's viewing appointments
header("Content-Type: application/json");
session_start();

include_once __DIR__ . '/../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Please login to view your appointments"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

try {
    $query = "SELECT vs.*, p.title, p.location, p.image_url, p.listing_type 
              FROM viewing_slots vs
              JOIN properties p ON vs.property_id = p.property_id
              WHERE vs.user_id = :user_id
              ORDER BY vs.viewing_date ASC, vs.viewing_time ASC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $viewings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format times for frontend if needed, or leave raw
    echo json_encode($viewings);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
