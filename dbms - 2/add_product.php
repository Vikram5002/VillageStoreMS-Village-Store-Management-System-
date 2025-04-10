<?php
header("Content-Type: application/json");
include "db_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "error" => "Invalid CSRF token"]);
    exit;
}

if (!isset($data['shop_id']) || !isset($data['name']) || !isset($data['price']) || !isset($data['stock'])) {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

$sql = "INSERT INTO products (shop_id, name, price, stock) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isii", $data['shop_id'], $data['name'], $data['price'], $data['stock']);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
$conn->close();
?>