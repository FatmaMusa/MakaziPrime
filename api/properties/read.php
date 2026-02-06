<?php
// api/properties/read.php - Public API for filtered property listing
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once __DIR__ . '/../../config/db.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$min_price = isset($_GET['min_price']) ? (int) $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (int) $_GET['max_price'] : null;
$type_id = isset($_GET['type_id']) ? (int) $_GET['type_id'] : null;
$bedrooms = isset($_GET['bedrooms']) ? (int) $_GET['bedrooms'] : null;
$location = isset($_GET['location']) ? trim($_GET['location']) : null;
$status = isset($_GET['status']) ? trim($_GET['status']) : null;

// Build query
$query = "SELECT p.*, pt.name as type_name 
          FROM properties p 
          LEFT JOIN property_types pt ON p.type_id = pt.type_id 
          WHERE 1=1";

$params = [];

if ($min_price !== null) {
    $query .= " AND p.price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price !== null) {
    $query .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

if ($type_id !== null && $type_id > 0) {
    $query .= " AND p.type_id = :type_id";
    $params[':type_id'] = $type_id;
}

if ($bedrooms !== null && $bedrooms > 0) {
    $query .= " AND p.bedrooms >= :bedrooms";
    $params[':bedrooms'] = $bedrooms;
}

if ($location !== null && $location !== '') {
    $query .= " AND p.location LIKE :location";
    $params[':location'] = '%' . $location . '%';
}

if ($status !== null && $status !== '') {
    $query .= " AND p.status = :status";
    $params[':status'] = $status;
} else {
    // By default, show only available and reserved (not sold) for public
    $query .= " AND p.status != 'sold'";
}

$query .= " ORDER BY p.created_at DESC";

try {
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($properties);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
