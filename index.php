<?php
session_start();
require_once 'database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $first_name = trim(htmlspecialchars($_POST['first_name'] ?? ''));
    $last_name = trim(htmlspecialchars($_POST['last_name'] ?? ''));
    $user_name = trim(htmlspecialchars($_POST['user_name'] ?? ''));
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $courses = $_POST['courses'] ?? [];
    $education = $_POST['education'] ?? '';
    $role = $_POST['role'] ?? '';
    $age = trim(htmlspecialchars($_POST['age'] ?? ''));
    $phone = trim(htmlspecialchars($_POST['phone'] ?? ''));

    // Validate inputs
    if (empty($first_name) || !preg_match("/^[a-zA-Z ]{2,50}$/", $first_name)) {
        $errors[] = "First name must be 2-50 letters only.";
    }
    if (empty($last_name) || !preg_match("/^[a-zA-Z ]{2,50}$/", $last_name)) {
        $errors[] = "Last name must be 2-50 letters only.";
    }
    if (empty($user_name) || !preg_match("/^[a-zA-Z0-9_]{2,50}$/", $user_name)) {
        $errors[] = "Username must be 2-50 letters, numbers, or underscores.";
    }
    if (empty($email) || !preg_match("/^[a-zA-Z]*[0-9]+[a-zA-Z0-9]*@gmail\.com$/", $email)) {
        $errors[] = "Email must be a valid Gmail address with at least one number (e.g., john123@gmail.com).";
    }
    if (strlen($password) < 8 || !preg_match("/[A-Za-z0-9]/", $password)) {   
        $errors[] = "Password must be at least 8 characters with letters and numbers.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($courses)) {
        $errors[] = "Select at least one course.";
    }
    if (!in_array($education, ['btech', 'mtech', 'phd'])) {
        $errors[] = "Select a valid education level.";
    }
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = "Select a valid role.";
    }
    if (!is_numeric($age) || $age < 18 || $age > 100) {
        $errors[] = "Age must be between 18 and 100.";
    }
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must be 10 digits.";
    }

    // Handle file upload
    if (empty($errors)) {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 3 * 1024 * 1024; // 3MB

            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Only JPEG, PNG, or GIF files are allowed.";
            } if ($file['size'] > $max_size) {
                $errors[] = "File size must be less than 3MB.";
            } else {
                $ext = pathinfo($file['name'], flags: PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $upload_path = 'uploads/' . $filename;

                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $errors[] = "Failed to upload profile picture.";
                }
            }
        } else {
            $errors[] = "Profile picture is required.";
        }
    }

    // Check for duplicate username or email
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("SELECT * FROM user_details WHERE user_name = :user_name OR email = :email");
        $stmt->execute(['user_name' => $user_name, 'email' => $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists.";
        }
    }

    // Store in database if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO user_details (first_name, last_name, user_name, email, password, courses, education, role, age, phone, profile_picture)
             VALUES (:first_name, :last_name, :user_name, :email, :password, :courses, :education, :role, :age, :phone, :profile_picture)"
        );
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_name' => $user_name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'courses' => implode(',', $courses),
            'education' => $education,
            'role' => $role,
            'age' => $age,
            'phone' => $phone,
            'profile_picture' => $upload_path ?? null,
        ]);

        $_SESSION['user'] = [
            'id' => $conn->lastInsertId(),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_name' => $user_name,
            'email' => $email,
            'courses' => $courses,
            'education' => $education,
            'role' => $role,
            'age' => $age,
            'phone' => $phone,
            'profile_picture' => $upload_path ?? null,
        ];
        $_SESSION['logged_in'] = true;
        $success = "Registration successful! <a href='login.php' class='text-blue-600 underline'>Go to Dashboard</a>.";
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PHP Session System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-purple-200 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-xl">
        <h2 class="text-3xl font-bold text-purple-700 mb-6 text-center">Register</h2>
        
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label for="first_name" class="block text-gray-700">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name ?? ''); ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="last_name" class="block text-gray-700">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name ?? ''); ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="user_name" class="block text-gray-700">Username</label>
                <input type="text" id="user_name" name="user_name" value="<?php echo htmlspecialchars($user_name ?? ''); ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="email" class="block text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" id="password" name="password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="confirm_password" class="block text-gray-700">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label class="block text-gray-700 mb-1">Courses</label>
                <div class="flex flex-wrap gap-4">
                    <?php $course_options = ['php', 'angular', 'python', 'react']; ?>
                    <?php foreach ($course_options as $course): ?>
                        <label class="flex items-center">
                            <input type="checkbox" name="courses[]" value="<?php echo $course; ?>" <?php echo in_array($course, $courses ?? []) ? 'checked' : ''; ?> class="mr-2">
                            <?php echo ucfirst($course); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 mb-1">Current Education</label>
                <div class="space-x-4">
                    <?php $education_options = ['btech' => 'B.Tech', 'mtech' => 'M.Tech', 'phd' => 'PhD']; ?>
                    <?php foreach ($education_options as $value => $label): ?>
                        <label class="inline-flex items-center">
                            <input type="radio" name="education" value="<?php echo $value; ?>" <?php echo ($education ?? '') === $value ? 'checked' : ''; ?> class="mr-2">
                            <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-gray-700">Role</label>
                <select name="role" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
                    <option value="">Select Role</option>
                    <option value="admin" <?php echo ($role ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo ($role ?? '') === 'user' ? 'selected' : ''; ?>>User</option>
                </select>
            </div>
            <div>
                <label for="age" class="block text-gray-700">Age</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($age ?? ''); ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="phone" class="block text-gray-700">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div>
                <label for="profile_picture" class="block text-gray-700">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
            </div>
            <div class="text-center">
                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-xl hover:bg-purple-700 transition">Register</button>
            </div>
        </form>
        <p class="mt-4 text-center">Already registered? <a href="login.php" class="text-purple-600 underline">Login here</a></p>
    </div>
</body>
</html>
