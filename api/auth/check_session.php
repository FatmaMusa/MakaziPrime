<?php
// api/auth/check_session.php
header("Content-Type: application/json");
session_start();

// Check if any user is logged in (admin or customer)
if (isset($_SESSION['user_id'])) {
    http_response_code(200);
    echo json_encode([
        "loggedIn" => true,
        "user" => [
            "id" => $_SESSION['user_id'],
            "name" => $_SESSION['full_name'],
            "email" => $_SESSION['email'] ?? '',
            "role" => $_SESSION['role']
        ]
    ]);
} else {
    http_response_code(200); // Return 200 for easier frontend handling
    echo json_encode([
        "loggedIn" => false,
        "message" => "Not logged in"
    ]);
}
