<?php
// api/viewings/create.php - Book a viewing slot
header("Content-Type: application/json");
session_start();

include_once __DIR__ . '/../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Please login to book a viewing"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->property_id) || empty($data->viewing_date) || empty($data->viewing_time)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required fields"]);
    exit;
}

$property_id = (int) $data->property_id;
$viewing_date = $data->viewing_date;
$viewing_time = $data->viewing_time;
$user_id = $_SESSION['user_id'];
$notes = $data->notes ?? '';

try {
    // Check if property exists and is available
    $checkProp = $db->prepare("SELECT status FROM properties WHERE property_id = :id");
    $checkProp->bindParam(':id', $property_id);
    $checkProp->execute();
    $prop = $checkProp->fetch(PDO::FETCH_ASSOC);

    if (!$prop) {
        http_response_code(404);
        echo json_encode(["message" => "Property not found"]);
        exit;
    }

    if ($prop['status'] === 'sold') {
        http_response_code(400);
        echo json_encode(["message" => "This property has been sold"]);
        exit;
    }

    // Check for existing booking on same date/time
    $checkSlot = $db->prepare("SELECT slot_id FROM viewing_slots 
                               WHERE property_id = :prop_id 
                               AND viewing_date = :date 
                               AND viewing_time = :time 
                               AND status != 'cancelled'");
    $checkSlot->bindParam(':prop_id', $property_id);
    $checkSlot->bindParam(':date', $viewing_date);
    $checkSlot->bindParam(':time', $viewing_time);
    $checkSlot->execute();

    if ($checkSlot->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "This time slot is already booked. Please choose another time."]);
        exit;
    }

    // Create the viewing slot
    $query = "INSERT INTO viewing_slots (property_id, user_id, viewing_date, viewing_time, notes) 
              VALUES (:prop_id, :user_id, :date, :time, :notes)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':prop_id', $property_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':date', $viewing_date);
    $stmt->bindParam(':time', $viewing_time);
    $stmt->bindParam(':notes', $notes);
    $stmt->execute();

    echo json_encode([
        "message" => "Viewing booked successfully!",
        "slot_id" => $db->lastInsertId()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
