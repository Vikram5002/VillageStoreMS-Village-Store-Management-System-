<?php
header("Content-Type: application/json");
include "db_connect.php";

$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
if ($shop_id == 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT product_id, name, price, stock FROM products WHERE shop_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) $products[] = $row;
echo json_encode($products);
$conn->close();
?>