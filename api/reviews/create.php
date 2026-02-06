<?php
// api/reviews/create.php - Submit a property review
header("Content-Type: application/json");
session_start();

include_once __DIR__ . '/../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Please login to submit a review"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->property_id) || empty($data->rating)) {
    http_response_code(400);
    echo json_encode(["message" => "Property ID and rating required"]);
    exit;
}

$property_id = (int) $data->property_id;
$rating = (int) $data->rating;
$comment = $data->comment ?? '';
$user_id = $_SESSION['user_id'];

// Validate rating
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(["message" => "Rating must be between 1 and 5"]);
    exit;
}

try {
    // Check if user already reviewed this property
    $checkQuery = "SELECT review_id FROM reviews WHERE user_id = :user_id AND property_id = :prop_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->bindParam(':prop_id', $property_id);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Update existing review
        $updateQuery = "UPDATE reviews SET rating = :rating, comment = :comment WHERE user_id = :user_id AND property_id = :prop_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':rating', $rating);
        $updateStmt->bindParam(':comment', $comment);
        $updateStmt->bindParam(':user_id', $user_id);
        $updateStmt->bindParam(':prop_id', $property_id);
        $updateStmt->execute();

        echo json_encode(["message" => "Review updated successfully"]);
    } else {
        // Insert new review
        $insertQuery = "INSERT INTO reviews (user_id, property_id, rating, comment) VALUES (:user_id, :prop_id, :rating, :comment)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':user_id', $user_id);
        $insertStmt->bindParam(':prop_id', $property_id);
        $insertStmt->bindParam(':rating', $rating);
        $insertStmt->bindParam(':comment', $comment);
        $insertStmt->execute();

        echo json_encode(["message" => "Review submitted successfully"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
