<?php
header("Content-Type: application/json");
include "db_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "error" => "Invalid CSRF token"]);
    exit;
}

if (!isset($data['order_id'])) {
    echo json_encode(["success" => false, "error" => "Missing order ID"]);
    exit;
}

$conn->begin_transaction();
try {
    // Verify order is in Pending status
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $data['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order || $order['status'] !== 'Pending') {
        throw new Exception("Order cannot be cancelled");
    }

    // Update order status to Cancelled
    $sql = "UPDATE orders SET status = 'Cancelled' WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data['order_id']);
    $stmt->execute();

    // Restore stock
    $sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data['order_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sql_stock = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
    $stmt_stock = $conn->prepare($sql_stock);
    
    while ($item = $result->fetch_assoc()) {
        $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }

    $conn->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
$conn->close();
?>