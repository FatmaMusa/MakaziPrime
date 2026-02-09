<?php
// api/bookings/create.php - Reserve a property
header("Content-Type: application/json");
session_start();
include_once __DIR__ . '/../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Please login to reserve a property"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->property_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Property ID is required"]);
    exit;
}

$property_id = (int) $data->property_id;
$user_id = $_SESSION['user_id'];
$reservation_fee = 50000; // Default flat fee as per plan

try {
    $db->beginTransaction();

    // 1. Get property details (to get the price)
    $propQuery = "SELECT price, status FROM properties WHERE property_id = :id";
    $stmtProp = $db->prepare($propQuery);
    $stmtProp->bindParam(':id', $property_id);
    $stmtProp->execute();
    $property = $stmtProp->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        throw new Exception("Property not found");
    }

    if ($property['status'] !== 'available') {
        throw new Exception("This property is no longer available for reservation");
    }

    // 2. Create the main booking record
    $bookingQuery = "INSERT INTO bookings (user_id, total_fee, status) VALUES (:user_id, :fee, 'pending')";
    $stmtBooking = $db->prepare($bookingQuery);
    $stmtBooking->bindParam(':user_id', $user_id);
    $stmtBooking->bindParam(':fee', $reservation_fee);
    $stmtBooking->execute();
    $booking_id = $db->lastInsertId();

    // 3. Create the booking detail record
    $detailQuery = "INSERT INTO booking_details (booking_id, property_id, agreed_price) VALUES (:booking_id, :property_id, :price)";
    $stmtDetail = $db->prepare($detailQuery);
    $stmtDetail->bindParam(':booking_id', $booking_id);
    $stmtDetail->bindParam(':property_id', $property_id);
    $stmtDetail->bindParam(':price', $property['price']);
    $stmtDetail->execute();

    // 4. Optionally update property status (commented out if admin wants to do it manually after approval)
    // $updateProp = "UPDATE properties SET status = 'reserved' WHERE property_id = :id";
    // $stmtUpdate = $db->prepare($updateProp);
    // $stmtUpdate->bindParam(':id', $property_id);
    // $stmtUpdate->execute();

    $db->commit();

    echo json_encode([
        "message" => "Property reserved successfully! Our team will contact you for the reservation fee payment.",
        "booking_id" => $booking_id
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(["message" => "Error: " . $e->getMessage()]);
}
?>