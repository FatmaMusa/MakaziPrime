<?php
// api/properties/update.php
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

// Since PHP doesn't handle PUT/PATCH with FormData well (for files), we usually use POST for updates with a flag or just POST
// But let's check method. If it's pure JSON update, we use php://input. If file upload is involved, it must be POST global.
// For simplicity in this vanilla project, allow POST for updates.

$id = $_POST['property_id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["message" => "Property ID required."]);
    exit();
}

// Handle Image Upload if present
$image_sql = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (in_array($_FILES['image']['type'], $allowed)) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], "../../assets/uploads/" . $filename)) {
            $image_sql = ", image_url = 'assets/uploads/$filename'";
        }
    }
}

$title = $_POST['title'];
$type_id = $_POST['type_id'];
$listing_type = $_POST['listing_type'] ?? 'sale';
$price = $_POST['price'];
$rent_period = ($listing_type === 'rent') ? ($_POST['rent_period'] ?? 'monthly') : null;
$location = $_POST['location'];
$description = $_POST['description'];
$status = $_POST['status'];
$bedrooms = $_POST['bedrooms'];
$bathrooms = $_POST['bathrooms'];
$area_sqft = $_POST['area_sqft'];

$query = "UPDATE properties SET 
    type_id = :type, 
    listing_type = :listing_type,
    title = :title, 
    description = :desc, 
    location = :loc, 
    price = :price,
    rent_period = :rent_period,
    bedrooms = :beds,
    bathrooms = :baths,
    area_sqft = :area,
    status = :status 
    $image_sql
    WHERE property_id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(":type", $type_id);
$stmt->bindParam(":listing_type", $listing_type);
$stmt->bindParam(":title", $title);
$stmt->bindParam(":desc", $description);
$stmt->bindParam(":loc", $location);
$stmt->bindParam(":price", $price);
$stmt->bindParam(":rent_period", $rent_period);
$stmt->bindParam(":beds", $bedrooms);
$stmt->bindParam(":baths", $bathrooms);
$stmt->bindParam(":area", $area_sqft);
$stmt->bindParam(":status", $status);
$stmt->bindParam(":id", $id);

if ($stmt->execute()) {
    // Log
    $log_q = "INSERT INTO admin_logs (admin_id, action) VALUES (:admin_id, :action)";
    $log_stmt = $db->prepare($log_q);
    $action = "Updated Property ID: $id";
    $log_stmt->bindParam(":admin_id", $_SESSION['user_id']);
    $log_stmt->bindParam(":action", $action);
    $log_stmt->execute();

    http_response_code(200);
    echo json_encode(["message" => "Property updated."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Update failed."]);
}
?>