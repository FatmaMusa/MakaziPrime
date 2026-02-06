<?php
// api/properties/read_admin.php
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

$query = "SELECT p.*, pt.name as type_name 
          FROM properties p
          JOIN property_types pt ON p.type_id = pt.type_id
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($properties);
?>