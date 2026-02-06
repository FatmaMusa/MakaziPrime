<?php
// api/properties/types.php - Get all property types
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once __DIR__ . '/../../config/db.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT * FROM property_types ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
