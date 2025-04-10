<?php
header("Content-Type: application/json");
include "db_connect.php";

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;

if ($customer_id == 0 && $shop_id == 0) {
    echo json_encode([]);
    exit;
}

$where_clause = $customer_id ? "o.customer_id = ?" : "o.shop_id = ?";
$param = $customer_id ?: $shop_id;

$sql = "SELECT o.order_id, c.name AS customer_name, o.order_date, 
        d.delivery_type, d.delivery_charge, d.delivery_address,
        o.status, oi.product_id, oi.quantity, p.name AS product_name
        FROM orders o 
        JOIN customers c ON o.customer_id = c.customer_id 
        LEFT JOIN delivery_details d ON o.order_id = d.order_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE $where_clause";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $param);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
$current_order = null;
while ($row = $result->fetch_assoc()) {
    if (!$current_order || $current_order['order_id'] != $row['order_id']) {
        if ($current_order) $orders[] = $current_order;
        $current_order = [
            'order_id' => $row['order_id'],
            'customer_name' => $row['customer_name'],
            'order_date' => $row['order_date'],
            'delivery_type' => $row['delivery_type'],
            'delivery_charge' => $row['delivery_charge'],
            'delivery_address' => $row['delivery_address'],
            'status' => $row['status'],
            'items' => []
        ];
    }
    if ($row['product_id']) {
        $current_order['items'][] = [
            'product_id' => $row['product_id'],
            'quantity' => $row['quantity'],
            'name' => $row['product_name']
        ];
    }
}
if ($current_order) $orders[] = $current_order;

echo json_encode($orders);
$conn->close();
?>