<?php
// api/properties/create.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit();
}

// Check if image is uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["message" => "Image is required."]);
    exit();
}

$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($_FILES['image']['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid image type. Only JPG, PNG, WEBP allowed."]);
    exit();
}

// Generate unique filename
$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$filename = uniqid() . "." . $ext;
$target_dir = "../../assets/uploads/";
$target_file = $target_dir . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
    http_response_code(500);
    echo json_encode(["message" => "Failed to upload image."]);
    exit();
}

// Prepare Data
$title = $_POST['title'];
$type_id = $_POST['type_id'];
$listing_type = $_POST['listing_type'] ?? 'sale';
$price = $_POST['price'];
$rent_period = ($listing_type === 'rent') ? ($_POST['rent_period'] ?? 'monthly') : null;
$location = $_POST['location'];
$description = $_POST['description'] ?? '';
$bedrooms = $_POST['bedrooms'] ?? 0;
$bathrooms = $_POST['bathrooms'] ?? 0;
$area_sqft = $_POST['area_sqft'] ?? 0;
$status = 'available';
$image_url = 'assets/uploads/' . $filename;

$query = "INSERT INTO properties (type_id, listing_type, title, description, location, price, rent_period, bedrooms, bathrooms, area_sqft, status, image_url) 
          VALUES (:type_id, :listing_type, :title, :description, :location, :price, :rent_period, :bedrooms, :bathrooms, :area_sqft, :status, :image_url)";

$stmt = $db->prepare($query);
$stmt->bindParam(":type_id", $type_id);
$stmt->bindParam(":listing_type", $listing_type);
$stmt->bindParam(":title", $title);
$stmt->bindParam(":description", $description);
$stmt->bindParam(":location", $location);
$stmt->bindParam(":price", $price);
$stmt->bindParam(":rent_period", $rent_period);
$stmt->bindParam(":bedrooms", $bedrooms);
$stmt->bindParam(":bathrooms", $bathrooms);
$stmt->bindParam(":area_sqft", $area_sqft);
$stmt->bindParam(":status", $status);
$stmt->bindParam(":image_url", $image_url);

if ($stmt->execute()) {
    // Log
    $prop_id = $db->lastInsertId();
    $log_q = "INSERT INTO admin_logs (admin_id, action) VALUES (:admin_id, :action)";
    $log_stmt = $db->prepare($log_q);
    $action = "Created Property ID: $prop_id ($title)";
    $log_stmt->bindParam(":admin_id", $_SESSION['user_id']);
    $log_stmt->bindParam(":action", $action);
    $log_stmt->execute();

    http_response_code(201);
    echo json_encode(["message" => "Property created successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database error."]);
}
?>