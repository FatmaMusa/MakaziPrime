<?php
// api/auth/login.php
header("Content-Type: application/json");

// Disable error display to prevent JSON breakage
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once __DIR__ . '/../../config/db.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->email) || empty($data->password)) {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
        exit();
    }

    // Handle "Remember Me" - extend session lifetime
    $rememberMe = isset($data->remember_me) && $data->remember_me === true;

    if ($rememberMe) {
        // 30 days session
        ini_set('session.cookie_lifetime', 60 * 60 * 24 * 30);
        ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
    }

    session_start();

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($data->password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['email'] = $row['email'];

            http_response_code(200);
            echo json_encode([
                "message" => "Login successful.",
                "user" => [
                    "id" => $row['user_id'],
                    "name" => $row['full_name'],
                    "email" => $row['email'],
                    "role" => $row['role']
                ]
            ]);

            // Log admin logins
            if ($row['role'] === 'admin') {
                $log_query = "INSERT INTO admin_logs (admin_id, action) VALUES (:id, 'Admin Logged In')";
                $log_stmt = $db->prepare($log_query);
                $log_stmt->bindParam(":id", $row['user_id']);
                $log_stmt->execute();
            }

        } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid password."]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["message" => "User not found."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
}
