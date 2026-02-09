<?php
// api/bookings/read.php - Get user's reservations
header("Content-Type: application/json");
session_start();
include_once __DIR__ . '/../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Please login to view your reservations"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

try {
    $query = "SELECT b.booking_id, b.booking_date, b.total_fee, b.status, 
                     p.title as property_title, p.location, p.image_url, p.listing_type
              FROM bookings b
              JOIN booking_details bd ON b.booking_id = bd.booking_id
              JOIN properties p ON bd.property_id = p.property_id
              WHERE b.user_id = :user_id
              ORDER BY b.booking_date DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($bookings);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>