<?php
// api/bookings/read_admin.php
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

$query = "SELECT b.booking_id, b.booking_date, b.total_fee, b.status,
                 u.full_name as user_name, u.email as user_email,
                 p.title as property_title, p.location
          FROM bookings b
          JOIN users u ON b.user_id = u.user_id
          -- Use LEFT JOIN on details if we want to show multiple props per booking or single
          -- For simplicity, let's assume one main property or just fetch details separately
          -- Let's JOIN with details to get the first property for display
          LEFT JOIN booking_details bd ON b.booking_id = bd.booking_id
          LEFT JOIN properties p ON bd.property_id = p.property_id
          GROUP BY b.booking_id
          ORDER BY b.booking_date DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($bookings);
?>