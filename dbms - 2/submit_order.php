<?php
header("Content-Type: application/json");
include "db_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "error" => "Invalid CSRF token"]);
    exit;
}

if (!$data || !isset($data['customer_id']) || !isset($data['shop_id']) || empty($data['items'])) {
    echo json_encode(["success" => false, "error" => "Missing or invalid required fields"]);
    exit;
}

$conn->begin_transaction();
try {
    // Check stock availability
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product || $product['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for product ID " . $item['product_id']);
        }
    }

    // Insert order
    $sql = "INSERT INTO orders (customer_id, shop_id, order_date, status) VALUES (?, ?, NOW(), 'Pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $data['customer_id'], $data['shop_id']);
    $stmt->execute();
    $order_id = $conn->insert_id;

    // Insert delivery details
    $sql = "INSERT INTO delivery_details (order_id, delivery_type, delivery_charge, delivery_address) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $order_id, $data['delivery_type'], $data['delivery_charge'], $data['delivery_address']);
    $stmt->execute();

    // Insert order items and update stock
    $sql_item = "INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)";
    $sql_stock = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
    $stmt_item = $conn->prepare($sql_item);
    $stmt_stock = $conn->prepare($sql_stock);
    
    $order_details = [];
    foreach ($data['items'] as $item) {
        $stmt_item->bind_param("iii", $order_id, $item['product_id'], $item['quantity']);
        $stmt_item->execute();
        $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
        
        // Collect order details for email simulation
        $stmt = $conn->prepare("SELECT name FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $order_details[] = "{$product['name']} (x{$item['quantity']})";
    }

    // Simulate email by logging to file
    $email_content = "Order #$order_id placed on " . date('Y-m-d H:i:s') . "\n";
    $email_content .= "Customer ID: {$data['customer_id']}\n";
    $email_content .= "Items: " . implode(", ", $order_details) . "\n";
    $email_content .= "Delivery: {$data['delivery_type']} (₹{$data['delivery_charge']})\n";
    if ($data['delivery_address']) {
        $email_content .= "Address: {$data['delivery_address']}\n";
    }
    $email_content .= "----------------------------------------\n";
    file_put_contents("emails.log", $email_content, FILE_APPEND);

    $conn->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
$conn->close();
?>