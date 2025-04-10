<?php
include "db_connect.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT name FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History - Village Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn { transition: all 0.2s ease; }
        .btn:hover { transform: scale(1.05); }
        .table-row:hover { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="container mx-auto p-6">
        <header class="flex justify-between items-center mb-8 bg-white p-4 rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-gray-800">Order History</h1>
            <div class="space-x-4">
                <a href="index.php" class="text-blue-500 hover:text-blue-700 font-medium">Back to Shopping</a>
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($customer['name'] ?? 'User'); ?>!</span>
                <a href="logout.php" class="text-blue-500 hover:text-blue-700 font-medium">Logout</a>
            </div>
        </header>

        <section class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Your Orders</h2>
            <div class="overflow-x-auto">
                <table id="order-table" class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200 text-gray-700">
                            <th class="p-3 text-left">Order ID</th>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Delivery Type</th>
                            <th class="p-3 text-left">Charge</th>
                            <th class="p-3 text-left">Address</th>
                            <th class="p-3 text-left">Items</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600"></tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';

        fetch(`get_orders.php?customer_id=<?php echo $customer_id; ?>`)
            .then(res => res.json())
            .then(data => {
                const table = document.getElementById("order-table").querySelector("tbody");
                table.innerHTML = "";
                data.forEach(o => {
                    const itemsList = o.items.map(item => `${item.name} (x${item.quantity})`).join(", ");
                    const canCancel = o.status === 'Pending';
                    table.innerHTML += `
                        <tr class="table-row">
                            <td class="p-3">${o.order_id}</td>
                            <td class="p-3">${o.order_date}</td>
                            <td class="p-3">${o.delivery_type}</td>
                            <td class="p-3">₹${o.delivery_charge}</td>
                            <td class="p-3">${o.delivery_address || 'N/A'}</td>
                            <td class="p-3">${itemsList || 'No items'}</td>
                            <td class="p-3">${o.status}</td>
                            <td class="p-3">
                                ${canCancel ? `<button class="btn bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600" onclick="cancelOrder(${o.order_id})">Cancel</button>` : ''}
                            </td>
                        </tr>`;
                });
            });

        function cancelOrder(orderId) {
            if (confirm("Are you sure you want to cancel this order?")) {
                fetch("cancel_order.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ order_id: orderId, csrf_token: csrfToken })
                }).then(res => res.json()).then(data => {
                    if (data.success) location.reload();
                    else alert("Error: " + data.error);
                });
            }
        }
    </script>
</body>
</html>