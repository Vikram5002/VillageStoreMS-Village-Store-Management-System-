<?php
header("Content-Type: application/json");
include "db_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "error" => "Invalid CSRF token"]);
    exit;
}

if (!isset($data['product_id']) || !isset($data['additional_stock'])) {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

$product_id = (int)$data['product_id'];
$additional_stock = (int)$data['additional_stock'];
$shop_id = $_SESSION['shop_id']; // Ensure the admin can only update their shop's products

// Verify the product belongs to the admin's shop
$stmt = $conn->prepare("SELECT shop_id FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product || $product['shop_id'] !== $shop_id) {
    echo json_encode(["success" => false, "error" => "Product not found or unauthorized"]);
    exit;
}

// Update the stock
$sql = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $additional_stock, $product_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$conn->close();
?>