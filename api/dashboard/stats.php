<?php
// api/dashboard/stats.php
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

$stats = [];

// 1. Total Properties (Active vs Sold)
$query_props = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold
FROM properties";
$stmt = $db->prepare($query_props);
$stmt->execute();
$props = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['properties'] = $props;

// 2. Pending Bookings
$query_bookings = "SELECT COUNT(*) as pending FROM bookings WHERE status = 'pending'";
$stmt = $db->prepare($query_bookings);
$stmt->execute();
$bookings = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['pending_bookings'] = $bookings['pending'];

// 3. Total Revenue
$query_revenue = "SELECT SUM(total_fee) as revenue FROM bookings WHERE status = 'approved'";
$stmt = $db->prepare($query_revenue);
$stmt->execute();
$revenue = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['revenue'] = $revenue['revenue'] ? $revenue['revenue'] : 0;

// 4. Pending Viewings
$query_viewings = "SELECT COUNT(*) as pending FROM viewing_slots WHERE status = 'pending'";
$stmt = $db->prepare($query_viewings);
$stmt->execute();
$viewings_count = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['pending_viewings'] = $viewings_count['pending'];

// 5. Recent Activity (Last 5 Bookings or New Users)
// Let's just pull last 5 bookings for now as 'activity'
$query_activity = "SELECT b.booking_id, u.full_name, b.booking_date, b.status 
                   FROM bookings b 
                   JOIN users u ON b.user_id = u.user_id 
                   ORDER BY b.booking_date DESC LIMIT 5";
$stmt = $db->prepare($query_activity);
$stmt->execute();
$activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats['recent_activity'] = $activity;

echo json_encode($stats);
?>