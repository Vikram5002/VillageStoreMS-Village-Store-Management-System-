<?php
try {
    $conn = new mysqli("localhost", "root", "", "shopp");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    die(json_encode(["error" => "Database connection failed"]));
}

// Generate CSRF token if not set
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>