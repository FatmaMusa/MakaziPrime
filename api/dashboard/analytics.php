<?php
// api/dashboard/analytics.php - Admin analytics data
header("Content-Type: application/json");
session_start();

include_once __DIR__ . '/../../config/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Monthly bookings (last 6 months)
    $monthlyBookings = [];
    $bookingsQuery = "SELECT 
                        DATE_FORMAT(booking_date, '%Y-%m') as month,
                        DATE_FORMAT(booking_date, '%b %Y') as month_label,
                        COUNT(*) as total_bookings,
                        SUM(total_fee) as revenue
                      FROM bookings 
                      WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
                      ORDER BY month ASC";
    $bookingsStmt = $db->query($bookingsQuery);
    $monthlyBookings = $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Property type distribution
    $typeDistQuery = "SELECT 
                        pt.name as type_name, 
                        COUNT(p.property_id) as count
                      FROM property_types pt
                      LEFT JOIN properties p ON pt.type_id = p.type_id
                      GROUP BY pt.type_id
                      ORDER BY count DESC";
    $typeDistStmt = $db->query($typeDistQuery);
    $typeDistribution = $typeDistStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Property status breakdown
    $statusQuery = "SELECT status, COUNT(*) as count FROM properties GROUP BY status";
    $statusStmt = $db->query($statusQuery);
    $statusBreakdown = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Recent bookings
    $recentQuery = "SELECT 
                      b.*,
                      u.full_name as user_name,
                      (SELECT p.title FROM booking_details bd 
                       JOIN properties p ON bd.property_id = p.property_id 
                       WHERE bd.booking_id = b.booking_id LIMIT 1) as property_title
                    FROM bookings b
                    JOIN users u ON b.user_id = u.user_id
                    ORDER BY b.booking_date DESC
                    LIMIT 5";
    $recentStmt = $db->query($recentQuery);
    $recentBookings = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Viewing requests summary
    $viewingsQuery = "SELECT 
                        status, 
                        COUNT(*) as count 
                      FROM viewing_slots 
                      GROUP BY status";
    $viewingsStmt = $db->query($viewingsQuery);
    $viewingsStats = $viewingsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Total revenue
    $revenueQuery = "SELECT COALESCE(SUM(total_fee), 0) as total_revenue FROM bookings WHERE status = 'approved'";
    $revenueStmt = $db->query($revenueQuery);
    $totalRevenue = $revenueStmt->fetch(PDO::FETCH_ASSOC);

    // 7. User registration trend
    $userTrendQuery = "SELECT 
                         DATE_FORMAT(created_at, '%b') as month,
                         COUNT(*) as new_users
                       FROM users 
                       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       AND role = 'buyer'
                       GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                       ORDER BY created_at ASC";
    $userTrendStmt = $db->query($userTrendQuery);
    $userTrend = $userTrendStmt->fetchAll(PDO::FETCH_ASSOC);

    // Compile response
    echo json_encode([
        "monthly_bookings" => $monthlyBookings,
        "type_distribution" => $typeDistribution,
        "status_breakdown" => $statusBreakdown,
        "recent_bookings" => $recentBookings,
        "viewings_stats" => $viewingsStats,
        "total_revenue" => $totalRevenue['total_revenue'],
        "user_trend" => $userTrend
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
