<?php
include "db_connect.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents("debug.log", "Login POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
        file_put_contents("debug.log", "CSRF Error: Token mismatch\n", FILE_APPEND);
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $type = $_POST['type'];

        if ($type == "customer") {
            $sql = "SELECT customer_id, password FROM customers WHERE username = ?";
            $redirect = "index.php";
        } else {
            $sql = "SELECT admin_id, password, shop_id FROM store_admins WHERE username = ?";
            $redirect = "admin.php";
        }

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = "Database prepare error: " . $conn->error;
            file_put_contents("debug.log", "Prepare Error: " . $conn->error . "\n", FILE_APPEND);
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            file_put_contents("debug.log", "User Found: " . ($user ? "Yes" : "No") . "\n", FILE_APPEND);

            if ($user && password_verify($password, $user['password'])) {
                if ($type == "customer") {
                    $_SESSION['customer_id'] = $user['customer_id'];
                    file_put_contents("debug.log", "Set customer_id: " . $user['customer_id'] . "\n", FILE_APPEND);
                } else {
                    $_SESSION['admin_id'] = $user['admin_id'];
                    $_SESSION['shop_id'] = $user['shop_id'];
                    file_put_contents("debug.log", "Set admin_id: " . $user['admin_id'] . ", shop_id: " . $user['shop_id'] . "\n", FILE_APPEND);
                }
                session_write_close();
                header("Location: $redirect");
                exit;
            } else {
                $error = "Invalid username or password";
                file_put_contents("debug.log", "Login Failed: Invalid credentials\n", FILE_APPEND);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Village Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .btn { transition: all 0.2s ease; }
        .btn:hover { transform: scale(1.05); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Login</h2>
        <?php if (isset($error)) echo "<p class='text-red-500 text-center mb-4'>$error</p>"; ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div>
                <select name="type" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
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
            <button type="submit" class="btn w-full bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Login</button>
        </form>
        <p class="text-center mt-4 text-gray-600">Don't have an account? <a href="signup.php" class="text-blue-500 hover:text-blue-700">Sign Up</a></p>
    </div>
</body>
</html>