<?php
// api/properties/read_single.php - Get single property details
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include_once __DIR__ . '/../../config/db.php';

$database = new Database();
$db = $database->getConnection();

$property_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$property_id) {
    http_response_code(400);
    echo json_encode(["message" => "Property ID required"]);
    exit;
}

try {
    // Get property details
    $query = "SELECT p.*, pt.name as type_name 
              FROM properties p 
              LEFT JOIN property_types pt ON p.type_id = pt.type_id 
              WHERE p.property_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $property_id);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "Property not found"]);
        exit;
    }

    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get reviews for this property
    $reviewQuery = "SELECT r.*, u.full_name 
                    FROM reviews r 
                    LEFT JOIN users u ON r.user_id = u.user_id 
                    WHERE r.property_id = :id 
                    ORDER BY r.created_at DESC 
                    LIMIT 10";
    $reviewStmt = $db->prepare($reviewQuery);
    $reviewStmt->bindParam(':id', $property_id);
    $reviewStmt->execute();
    $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    $avgQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                 FROM reviews WHERE property_id = :id";
    $avgStmt = $db->prepare($avgQuery);
    $avgStmt->bindParam(':id', $property_id);
    $avgStmt->execute();
    $ratingData = $avgStmt->fetch(PDO::FETCH_ASSOC);

    $property['reviews'] = $reviews;
    $property['avg_rating'] = round($ratingData['avg_rating'] ?? 0, 1);
    $property['total_reviews'] = (int) ($ratingData['total_reviews'] ?? 0);

    echo json_encode($property);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
