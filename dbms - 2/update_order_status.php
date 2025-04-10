<?php
header("Content-Type: application/json");
session_start(); // Critical for session access
include "db_connect.php";

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate CSRF token
if (!isset($data['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
    echo json_encode([
        "success" => false, 
        "error" => "Invalid CSRF token",
        "session_token" => $_SESSION['csrf_token'] ?? 'null',
        "received_token" => $data['csrf_token'] ?? 'null'
    ]);
    exit;
}

// Validate input
if (empty($data['order_id']) || empty($data['status'])) {
    echo json_encode(["success" => false, "error" => "Missing order ID or status"]);
    exit;
}

// Validate status value
$valid_statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled'];
if (!in_array($data['status'], $valid_statuses)) {
    echo json_encode(["success" => false, "error" => "Invalid status value"]);
    exit;
}

try {
    // Verify order belongs to admin's shop
    $stmt = $conn->prepare("SELECT shop_id FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $data['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order not found");
    }
    
    $order = $result->fetch_assoc();
    if ($order['shop_id'] != $_SESSION['shop_id']) {
        throw new Exception("Unauthorized access to order");
    }

    // Update status
    $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $update_stmt->bind_param("si", $data['status'], $data['order_id']);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            "success" => true,
            "affected_rows" => $update_stmt->affected_rows
        ]);
    } else {
        throw new Exception("Update failed: " . $conn->error);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>