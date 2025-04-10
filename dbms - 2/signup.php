<?php
include "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents("debug.log", "Signup POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
        file_put_contents("debug.log", "CSRF Error: Token mismatch\n", FILE_APPEND);
    } else {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $type = $_POST['type'];

        if ($type == "customer") {
            $name = !empty($_POST['name']) ? $_POST['name'] : null;
            $address = !empty($_POST['address']) ? $_POST['address'] : null;
            $sql = "INSERT INTO customers (username, password, name, address) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $password, $name, $address);
        } else {
            $shop_id = (int)$_POST['shop_id'];
            $sql = "INSERT INTO store_admins (username, password, shop_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $username, $password, $shop_id);
        }

        try {
            if ($stmt->execute()) {
                file_put_contents("debug.log", "Signup Success: $username\n", FILE_APPEND);
                header("Location: login.php");
                exit;
            } else {
                $error = "Error: Could not execute query - " . $stmt->error;
                file_put_contents("debug.log", "Query Error: " . $stmt->error . "\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            $error = "Error: Username already taken or invalid data - " . $e->getMessage();
            file_put_contents("debug.log", "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

$result = $conn->query("SELECT shop_id, name FROM shops");
$shops = [];
while ($row = $result->fetch_assoc()) $shops[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - Village Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn { transition: all 0.2s ease; }
        .btn:hover { transform: scale(1.05); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Sign Up</h2>
        <?php if (isset($error)) echo "<p class='text-red-500 text-center mb-4'>$error</p>"; ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div>
                <select name="type" onchange="toggleDetails()" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="customer">Customer</option>
                    <option value="admin">Store Admin</option>
                </select>
            </div>
            <div>
                <input type="text" name="username" placeholder="Username" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <input type="password" name="password" placeholder="Password" required class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div id="customer-details" class="space-y-4">
                <input type="text" name="name" placeholder="Your Name" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <input type="text" name="address" placeholder="Your Address" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div id="admin-details" class="hidden space-y-4">
                <select name="shop_id" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?php echo $shop['shop_id']; ?>"><?php echo htmlspecialchars($shop['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn w-full bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Sign Up</button>
        </form>
        <p class="text-center mt-4 text-gray-600">Already have an account? <a href="login.php" class="text-blue-500 hover:text-blue-700">Login</a></p>
    </div>
    <script>
        function toggleDetails() {
            const type = document.querySelector("select[name='type']").value;
            document.getElementById("customer-details").classList.toggle("hidden", type !== "customer");
            document.getElementById("admin-details").classList.toggle("hidden", type !== "admin");
        }
    </script>
</body>
</html>