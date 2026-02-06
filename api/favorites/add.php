<?php
// api/favorites/add.php - Toggle favorite (add/remove)
header("Content-Type: application/json");
session_start();

include_once __DIR__ . '/../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Please login to manage favorites"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->property_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Property ID required"]);
    exit;
}

$property_id = (int) $data->property_id;
$user_id = $_SESSION['user_id'];

try {
    // Check if already favorited
    $checkQuery = "SELECT favorite_id FROM favorites WHERE user_id = :user_id AND property_id = :prop_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->bindParam(':prop_id', $property_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Remove from favorites
        $deleteQuery = "DELETE FROM favorites WHERE user_id = :user_id AND property_id = :prop_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':user_id', $user_id);
        $deleteStmt->bindParam(':prop_id', $property_id);
        $deleteStmt->execute();

        echo json_encode(["message" => "Removed from favorites", "action" => "removed"]);
    } else {
        // Add to favorites
        $insertQuery = "INSERT INTO favorites (user_id, property_id) VALUES (:user_id, :prop_id)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':user_id', $user_id);
        $insertStmt->bindParam(':prop_id', $property_id);
        $insertStmt->execute();

        echo json_encode(["message" => "Added to favorites", "action" => "added"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
