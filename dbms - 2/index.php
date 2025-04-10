<?php
include "db_connect.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT name, address FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

$result = $conn->query("SELECT shop_id, name FROM shops");
$shops = [];
while ($row = $result->fetch_assoc()) $shops[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Village Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn { transition: all 0.2s ease; }
        .btn:hover { transform: scale(1.05); }
        .product-card { transition: all 0.2s ease; }
        .product-card:hover { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="container mx-auto p-6">
        <header class="flex justify-between items-center mb-8 bg-white p-4 rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-gray-800">Village Store</h1>
            <div class="space-x-4">
                <a href="history.php" class="text-blue-500 hover:text-blue-700 font-medium">Order History</a>
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($customer['name'] ?? 'User'); ?>!</span>
                <a href="logout.php" class="text-blue-500 hover:text-blue-700 font-medium">Logout</a>
            </div>
        </header>

        <section class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Here</h2>
            <div class="flex flex-col md:flex-row md:items-center md:space-x-4 mb-6">
                <select id="shop-id" onchange="loadProducts()" class="w-full md:w-1/3 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?php echo $shop['shop_id']; ?>"><?php echo htmlspecialchars($shop['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="search" placeholder="Search products..." oninput="filterProducts()" class="w-full md:w-1/3 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <div class="flex items-center space-x-2">
                    <input type="number" id="min-price" placeholder="Min Price" oninput="filterProducts()" class="w-1/2 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <input type="number" id="max-price" placeholder="Max Price" oninput="filterProducts()" class="w-1/2 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div id="products" class="space-y-4"></div>

            <div class="mt-6 border-t pt-4">
                <label class="block text-gray-800 font-medium mb-2">Delivery Option:</label>
                <select id="delivery-type" onchange="toggleDelivery()" class="w-full md:w-1/3 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="pickup">Pickup (Free)</option>
                    <option value="delivery">Delivery (₹50)</option>
                </select>
                <div id="delivery-address" class="mt-4 hidden">
                    <label class="block text-gray-800 font-medium mb-2">Address:</label>
                    <input type="text" id="address-input" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="mt-6 flex justify-between items-center">
                <p class="text-lg font-semibold text-gray-800">Total: ₹<span id="total">0</span></p>
                <button onclick="placeOrder()" class="btn bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Order Now</button>
            </div>
        </section>
    </div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        const deliveryCharge = 50;
        let products = [];

        function loadProducts() {
            const shopId = document.getElementById("shop-id").value;
            fetch(`get_products.php?shop_id=${shopId}`)
                .then(res => res.json())
                .then(data => {
                    products = data;
                    renderProducts(products);
                });
        }

        function renderProducts(filteredProducts) {
            const div = document.getElementById("products");
            div.innerHTML = "";
            filteredProducts.forEach(p => {
                div.innerHTML += `
                    <div class="product-card flex items-center justify-between p-3 border rounded-lg">
                        <span class="text-gray-700">${p.name} (₹${p.price}) - Stock: ${p.stock}</span>
                        <input type="number" min="0" max="${p.stock}" value="0" data-id="${p.product_id}" onchange="updateTotal()" class="w-20 p-1 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>`;
            });
        }

        function filterProducts() {
            const search = document.getElementById("search").value.toLowerCase();
            const minPrice = parseInt(document.getElementById("min-price").value) || 0;
            const maxPrice = parseInt(document.getElementById("max-price").value) || Infinity;
            const filtered = products.filter(p =>
                p.name.toLowerCase().includes(search) && p.price >= minPrice && p.price <= maxPrice
            );
            renderProducts(filtered);
        }

        function toggleDelivery() {
            const deliveryType = document.getElementById("delivery-type").value;
            document.getElementById("delivery-address").classList.toggle("hidden", deliveryType !== "delivery");
            updateTotal();
        }

        function updateTotal() {
            let total = 0;
            document.querySelectorAll("[data-id]").forEach(input => {
                const qty = parseInt(input.value) || 0;
                const productId = input.getAttribute("data-id");
                const product = products.find(p => p.product_id == productId);
                if (product) total += qty * product.price;
            });
            const deliveryType = document.getElementById("delivery-type").value;
            if (deliveryType === "delivery") total += deliveryCharge;
            document.getElementById("total").textContent = total.toFixed(2);
        }

        function placeOrder() {
            const items = [];
            document.querySelectorAll("[data-id]").forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    const productId = input.getAttribute("data-id");
                    items.push({ product_id: productId, quantity: qty });
                }
            });
            if (items.length === 0) {
                alert("Please select at least one item.");
                return;
            }
            const deliveryType = document.getElementById("delivery-type").value;
            const deliveryCharge = deliveryType === "delivery" ? 50 : 0;
            const deliveryAddress = deliveryType === "delivery" ? document.getElementById("address-input").value : null;

            const order = {
                customer_id: <?php echo $customer_id; ?>,
                shop_id: document.getElementById("shop-id").value,
                items: items,
                delivery_type: deliveryType,
                delivery_charge: deliveryCharge,
                delivery_address: deliveryAddress,
                csrf_token: csrfToken
            };

            fetch("submit_order.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(order)
            }).then(res => res.json()).then(data => {
                if (data.success) alert("Order placed successfully!");
                else alert("Error: " + data.error);
                location.reload();
            });
        }

        loadProducts();
    </script>
</body>
</html>