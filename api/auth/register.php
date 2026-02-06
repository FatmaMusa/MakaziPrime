<?php
// api/auth/register.php
header("Content-Type: application/json");

// Disable error display to prevent JSON breakage
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once __DIR__ . '/../../config/db.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    // Validate required fields
    if (empty($data->full_name) || empty($data->email) || empty($data->password)) {
        http_response_code(400);
        echo json_encode(["message" => "Please fill in all required fields."]);
        exit();
    }

    // Validate email format
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["message" => "Please enter a valid email address."]);
        exit();
    }

    // Validate password length
    if (strlen($data->password) < 6) {
        http_response_code(400);
        echo json_encode(["message" => "Password must be at least 6 characters."]);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = :email LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":email", $data->email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["message" => "An account with this email already exists."]);
        exit();
    }

    // Hash password and insert new user
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (full_name, email, password_hash, role) VALUES (:name, :email, :password, 'buyer')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":name", $data->full_name);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $password_hash);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "message" => "Registration successful! You can now login.",
            "user_id" => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to create account. Please try again."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
}
