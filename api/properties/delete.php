<?php
// api/properties/delete.php
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

$data = json_decode(file_get_contents("php://input"));
$id = $data->property_id ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["message" => "Property ID required."]);
    exit();
}

// Get Image URL to delete file
$q_img = "SELECT image_url FROM properties WHERE property_id = :id";
$s_img = $db->prepare($q_img);
$s_img->bindParam(":id", $id);
$s_img->execute();
$row = $s_img->fetch(PDO::FETCH_ASSOC);

if ($row && $row['image_url']) {
    $file_path = "../../" . $row['image_url'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

$query = "DELETE FROM properties WHERE property_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);

if ($stmt->execute()) {
    // Log
    $log_q = "INSERT INTO admin_logs (admin_id, action) VALUES (:admin_id, :action)";
    $log_stmt = $db->prepare($log_q);
    $action = "Deleted Property ID: $id";
    $log_stmt->bindParam(":admin_id", $_SESSION['user_id']);
    $log_stmt->bindParam(":action", $action);
    $log_stmt->execute();

    http_response_code(200);
    echo json_encode(["message" => "Property deleted."]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to delete."]);
}
?>