<?php
session_start();
require_once 'database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = trim(htmlspecialchars($_POST['user_name'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($user_name) || empty($password)) {
        $errors[] = "Username and password are required.";
    } else {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("SELECT * FROM user_details WHERE user_name = :user_name");
        $stmt->execute(['user_name' => $user_name]);
        $user = $stmt->fetch();

        if ($user || password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'user_name' => $user['user_name'],
                'email' => $user['email'],
                'courses' => explode(',', $user['courses']),
                'education' => $user['education'],
                'role' => $user['role'],
                'age' => $user['age'],
                'phone' => $user['phone'],
                'profile_picture' => $user['profile_picture'],
            ];
            $_SESSION['logged_in'] = true;
            if($user['role']==='user'){
                header("Location: userdashboard.php");
                exit();
            } else{
                header("Location: admindashboard.php");
                exit();
            }
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PHP Session System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-purple-200 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <h2 class="text-3xl font-bold text-purple-700 mb-6 text-center">Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="user_name" class="block text-gray-700">Username</label>
                <input type="text" id="user_name" name="user_name" value="<?php echo htmlspecialchars($user_name ?? ''); ?>" placeholder="Enter your username" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div class="text-center">
                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-xl hover:bg-purple-700 transition">Login</button>
            </div>
        </form>

        <p class="mt-4 text-center">Don't have an account? <a href="index.php" class="text-purple-600 underline">Register here</a></p>
    </div>
</body>
</html>
