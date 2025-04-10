<?php
include "db_connect.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$shop_id = $_SESSION['shop_id'];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents("debug.log", "Session Data in sales_analysis.php: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

$stmt = $conn->prepare("SELECT name FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$shop = $result->fetch_assoc() ?: ['name' => 'Unknown Shop'];

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Total revenue and order count
$sql = "SELECT 
    COUNT(DISTINCT o.order_id) AS total_orders,
    SUM(oi.quantity * p.price) AS total_revenue
FROM orders o
JOIN order_items oi ON o.order_id = oi.order_id
JOIN products p ON oi.product_id = p.product_id
WHERE o.shop_id = ? AND o.status = 'Delivered' AND o.order_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $shop_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$sales_summary = $result->fetch_assoc();
$total_orders = $sales_summary['total_orders'] ?? 0;
$total_revenue = $sales_summary['total_revenue'] ?? 0;

// Top products by quantity
$sql = "SELECT p.name, SUM(oi.quantity) AS total_quantity, SUM(oi.quantity * p.price) AS product_revenue
FROM orders o
JOIN order_items oi ON o.order_id = oi.order_id
JOIN products p ON oi.product_id = p.product_id
WHERE o.shop_id = ? AND o.status = 'Delivered' AND o.order_date BETWEEN ? AND ?
GROUP BY p.product_id, p.name
ORDER BY total_quantity DESC
LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $shop_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$top_products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Analysis - <?php echo htmlspecialchars($shop['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .btn { transition: all 0.2s ease; }
        .btn:hover { transform: scale(1.05); }
        .card { transition: all 0.2s ease; }
        .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="container mx-auto p-6">
        <header class="flex justify-between items-center mb-8 bg-white p-4 rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-gray-800">Sales Analysis - <?php echo htmlspecialchars($shop['name']); ?></h1>
            <div class="space-x-4">
                <a href="admin.php" class="text-blue-500 hover:text-blue-700 font-medium">Back to Admin</a>
                <a href="logout.php" class="text-blue-500 hover:text-blue-700 font-medium">Logout</a>
            </div>
        </header>

        <!-- Filters -->
        <section class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Sales</h2>
            <div class="flex flex-col md:flex-row md:items-center md:space-x-4">
                <input type="date" id="start-date" value="<?php echo $start_date; ?>" onchange="filterSales()" class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none mb-2 md:mb-0">
                <input type="date" id="end-date" value="<?php echo $end_date; ?>" onchange="filterSales()" class="p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none mb-2 md:mb-0">
            </div>
        </section>

        <!-- Sales Summary -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="card bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Revenue</h3>
                <p class="text-2xl font-bold text-green-600">₹<?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="card bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Orders</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $total_orders; ?></p>
            </div>
        </section>

        <!-- Top Products Chart -->
        <section class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Top 5 Products by Quantity</h2>
            <canvas id="salesChart" class="w-full h-64"></canvas>
        </section>
    </div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        const shop_id = <?php echo $shop_id; ?>;

        function filterSales() {
            const startDate = document.getElementById("start-date").value;
            const endDate = document.getElementById("end-date").value;
            window.location.href = `sales_analysis.php?start_date=${startDate}&end_date=${endDate}`;
        }

        // Chart.js setup
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($top_products, 'name')); ?>,
                datasets: [{
                    label: 'Quantity Sold',
                    data: <?php echo json_encode(array_column($top_products, 'total_quantity')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }, {
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode(array_column($top_products, 'product_revenue')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.6)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 1,
                    type: 'line'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>