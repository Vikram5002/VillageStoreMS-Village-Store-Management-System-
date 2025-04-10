<?php
include "db_connect.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$shop_id = $_SESSION['shop_id'];

// Debug mode - remove in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

$stmt = $conn->prepare("SELECT name FROM shops WHERE shop_id = ?");
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
    $shop = ['name' => 'Unknown Shop'];
} else {
    $stmt->bind_param("i", $shop_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $shop = ['name' => 'Unknown Shop'];
    } else {
        $result = $stmt->get_result();
        $shop = $result->fetch_assoc() ?: ['name' => 'Unknown Shop'];
    }
    $stmt->close();
}

$low_stock = [];
$stmt = $conn->prepare("SELECT product_id, name, stock FROM products WHERE shop_id = ? AND stock < 10");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $low_stock[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Village Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn { transition: all 0.2s ease; }
        .btn:hover { transform: scale(1.05); }
        .table-row { transition: background-color 0.2s ease; }
        .table-row:hover { background-color: #f1f5f9; }
        
        /* Status update styles */
        .updating {
            opacity: 0.7;
            background-color: #fffacd !important;
        }
        .updated {
            animation: highlight 2s;
        }
        @keyframes highlight {
            0% { background-color: #e6ffe6; }
            100% { background-color: inherit; }
        }
        .status-select {
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="container mx-auto p-6">
        <header class="flex justify-between items-center mb-8 bg-white p-4 rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-gray-800">Admin - <?php echo htmlspecialchars($shop['name']); ?></h1>
            <a href="logout.php" class="text-blue-500 hover:text-blue-700 font-medium">Logout</a>
        </header>

        <?php if (!empty($low_stock)): ?>
            <div class="bg-yellow-50 p-6 rounded-lg shadow-md mb-8 border-l-4 border-yellow-400">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">Low Stock Alert</h3>
                <ul class="list-disc pl-5 text-gray-700">
                    <?php foreach ($low_stock as $product): ?>
                        <li><?php echo htmlspecialchars($product['name']); ?> (ID: <?php echo $product['product_id']; ?>) - Stock: <?php echo $product['stock']; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Add Product</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" id="name" placeholder="Product Name" class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <input type="number" id="price" placeholder="Price" class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <input type="number" id="stock" placeholder="Stock" class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <button onclick="addProduct()" class="btn bg-green-500 text-white p-2 rounded-lg hover:bg-green-600">Add Product</button>
            </div>
        </section>

        <section class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Products</h2>
            <div class="overflow-x-auto">
                <table id="product-table" class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200 text-gray-700">
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">Name</th>
                            <th class="p-3 text-left">Price</th>
                            <th class="p-3 text-left">Stock</th>
                            <th class="p-3 text-left">Add Stock</th>
                            <th class="p-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600"></tbody>
                </table>
            </div>
        </section>

        <section class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Orders</h2>
            <div class="overflow-x-auto">
                <table id="order-table" class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200 text-gray-700">
                            <th class="p-3 text-left">Order ID</th>
                            <th class="p-3 text-left">Customer</th>
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

        // Enhanced status update function
        function updateOrderStatus(orderId, status) {
            const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
            if (!row) return;
            
            // Visual feedback
            row.classList.add('updating');
            const statusCell = row.querySelector('.order-status');
            const originalStatus = statusCell.textContent;
            statusCell.textContent = "Updating...";
            
            fetch("update_order_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    order_id: orderId,
                    status: status,
                    csrf_token: csrfToken
                })
            })
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update UI without reload
                    statusCell.textContent = status;
                    row.classList.add('updated');
                    setTimeout(() => row.classList.remove('updated'), 2000);
                    
                    // Update the dropdown to show new selected value
                    const select = row.querySelector('.status-select');
                    if (select) select.value = status;
                } else {
                    throw new Error(data.error || "Unknown error");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                statusCell.textContent = originalStatus;
                alert(`Failed to update status: ${error.message}`);
            })
            .finally(() => {
                row.classList.remove('updating');
            });
        }

        // Product management functions
        function addProduct() {
            const product = {
                shop_id: <?php echo $shop_id; ?>,
                name: document.getElementById("name").value,
                price: parseInt(document.getElementById("price").value),
                stock: parseInt(document.getElementById("stock").value),
                csrf_token: csrfToken
            };
            
            if (!product.name || isNaN(product.price) || isNaN(product.stock)) {
                alert("Please fill all fields correctly.");
                return;
            }
            
            fetch("add_product.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(product)
            }).then(res => res.json()).then(data => {
                if (data.success) location.reload();
                else alert("Error: " + (data.error || "Failed to add product"));
            });
        }

        function deleteProduct(id) {
            if (confirm("Are you sure you want to delete this product?")) {
                fetch("delete_product.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ product_id: id, csrf_token: csrfToken })
                }).then(res => res.json()).then(data => {
                    if (data.success) location.reload();
                    else alert("Error: " + data.error);
                });
            }
        }

        function addStock(productId) {
            const stockInput = document.getElementById(`stock-${productId}`);
            const additionalStock = parseInt(stockInput.value) || 0;
            if (additionalStock <= 0) {
                alert("Please enter a valid stock quantity to add.");
                return;
            }
            
            fetch("update_stock.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ 
                    product_id: productId, 
                    additional_stock: additionalStock, 
                    csrf_token: csrfToken 
                })
            }).then(res => res.json()).then(data => {
                if (data.success) location.reload();
                else alert("Error: " + data.error);
            });
        }

        // Load products and orders
        fetch(`get_products.php?shop_id=<?php echo $shop_id; ?>`)
            .then(res => res.json())
            .then(data => {
                const table = document.getElementById("product-table").querySelector("tbody");
                table.innerHTML = "";
                data.forEach(p => {
                    table.innerHTML += `
                        <tr class="table-row">
                            <td class="p-3">${p.product_id}</td>
                            <td class="p-3">${p.name}</td>
                            <td class="p-3">₹${p.price}</td>
                            <td class="p-3">${p.stock}</td>
                            <td class="p-3 flex space-x-2">
                                <input type="number" min="1" id="stock-${p.product_id}" 
                                    class="w-20 p-1 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                                    placeholder="Qty">
                                <button class="btn bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600" 
                                    onclick="addStock(${p.product_id})">Add</button>
                            </td>
                            <td class="p-3">
                                <button class="btn bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600" 
                                    onclick="deleteProduct(${p.product_id})">Delete</button>
                            </td>
                        </tr>`;
                });
            });

        fetch(`get_orders.php?shop_id=<?php echo $shop_id; ?>`)
            .then(res => res.json())
            .then(data => {
                const table = document.getElementById("order-table").querySelector("tbody");
                table.innerHTML = "";
                data.forEach(o => {
                    const itemsList = o.items.map(item => `${item.name} (x${item.quantity})`).join(", ");
                    const canCancel = o.status === 'Pending';
                    
                    table.innerHTML += `
                        <tr class="table-row" data-order-id="${o.order_id}">
                            <td class="p-3">${o.order_id}</td>
                            <td class="p-3">${o.customer_name}</td>
                            <td class="p-3">${o.order_date}</td>
                            <td class="p-3">${o.delivery_type}</td>
                            <td class="p-3">₹${o.delivery_charge}</td>
                            <td class="p-3">${o.delivery_address || 'N/A'}</td>
                            <td class="p-3">${itemsList || 'No items'}</td>
                            <td class="p-3 order-status">${o.status}</td>
                            <td class="p-3">
                                <select class="status-select" onchange="updateOrderStatus(${o.order_id}, this.value)">
                                    <option value="Pending" ${o.status === 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Shipped" ${o.status === 'Shipped' ? 'selected' : ''}>Shipped</option>
                                    <option value="Delivered" ${o.status === 'Delivered' ? 'selected' : ''}>Delivered</option>
                                    <option value="Cancelled" ${o.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                                ${canCancel ? `<button class="btn bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 ml-2" 
                                    onclick="cancelOrder(${o.order_id})">Cancel</button>` : ''}
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