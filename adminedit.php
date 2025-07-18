<?php
session_start();
require_once 'database.php';

// Check if admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get user ID from URL
$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: admindashboard.php');
    exit;
}

// Fetch user details
$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT * FROM user_details WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: admindashboard.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $first_name = trim(htmlspecialchars($_POST['first_name'] ?? ''));
    $last_name = trim(htmlspecialchars($_POST['last_name'] ?? ''));
    $user_name = trim(htmlspecialchars($_POST['user_name'] ?? ''));
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
    $age = trim(htmlspecialchars($_POST['age'] ?? ''));
    $education = $_POST['education'] ?? '';
    $courses = $_POST['courses'] ?? [];
    $role = $_POST['role'] ?? 'user';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

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
    if (empty($phone) || !preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must be 10 digits.";
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

    // Add other validations as needed...

    // Handle file upload if new picture provided
    $profile_picture = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 3 * 1024 * 1024; // 3MB

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            // Delete old picture if exists
            if (!empty($profile_picture) && file_exists($profile_picture)) {
                unlink($profile_picture);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_path = 'uploads/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $profile_picture = $upload_path;
            }
        }
    }

    // Update database if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "UPDATE user_details SET 
                first_name = :first_name,
                last_name = :last_name,
                user_name = :user_name,
                email = :email,
                phone = :phone,
                age = :age,
                education = :education,
                courses = :courses,
                role = :role,
                profile_picture = :profile_picture
             WHERE id = :id"
        );
        
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_name' => $user_name,
            'email' => $email,
            'phone' => $phone,
            'age' => $age,
            'education' => $education,
            'courses' => implode(',', $courses),
            'role' => $role,           
            'profile_picture' => $profile_picture,
            'id' => $userId
        ]);
        
        $success = "User updated successfully!";
        
        // If admin edited their own profile, update session
        if ($userId == $_SESSION['user']['id']) {
            $_SESSION['user'] = array_merge($_SESSION['user'], [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'user_name' => $user_name,
                'email' => $email,
                'phone' => $phone,
                'age' => $age,
                'education' => $education,
                'courses' => $courses,
                'role' => $role,
                'profile_picture' => $profile_picture
            ]);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Edit User</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit User: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                <a href="admindashboard.php" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div>
                        <!-- Profile Picture -->
                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">Profile Picture</label>
                            <div class="flex items-center space-x-4">
                                <div class="h-24 w-24 rounded-full overflow-hidden border-4 border-white shadow">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <div class="h-full w-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-400 text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input type="file" name="profile_picture" accept="image/*" class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100">
                                </div>
                            </div>
                        </div>

                        <!-- Personal Info -->
                        <div class="space-y-4">
                            <div>
                                <label for="first_name" class="block text-gray-700 font-bold mb-2">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                       class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            
                            <div>
                                <label for="last_name" class="block text-gray-700 font-bold mb-2">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                       class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            
                            <div>
                                <label for="user_name" class="block text-gray-700 font-bold mb-2">Username</label>
                                <input type="text" id="user_name" name="user_name" 
                                       value="<?php echo htmlspecialchars($user['user_name']); ?>" 
                                       class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-4">
                        
                        
                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-gray-700 font-bold mb-2">User Role</label>
                            <select id="role" name="role" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <!-- Contact Info -->
                        <div>
                            <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="block text-gray-700 font-bold mb-2">Phone</label>
                                <input type="text" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                       class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            <div>
                                <label for="age" class="block text-gray-700 font-bold mb-2">Age</label>
                                <input type="number" id="age" name="age" 
                                       value="<?php echo htmlspecialchars($user['age']); ?>" 
                                       class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                        </div>
                        
                        <!-- Education -->
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Education</label>
                            <div class="space-x-4">
                                <?php $education_options = ['btech' => 'B.Tech', 'mtech' => 'M.Tech', 'phd' => 'PhD']; ?>
                                <?php foreach ($education_options as $value => $label): ?>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="education" value="<?php echo $value; ?>" 
                                               <?php echo ($user['education'] ?? '') === $value ? 'checked' : ''; ?> 
                                               class="mr-2">
                                        <?php echo $label; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Courses -->
                <div>
                    <label class="block text-gray-700 font-bold mb-2">Courses</label>
                    <div class="flex flex-wrap gap-4">
                        <?php $course_options = ['php', 'angular', 'python', 'react']; ?>
                        <?php $user_courses = explode(',', $user['courses'] ?? ''); ?>
                        <?php foreach ($course_options as $course): ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="courses[]" value="<?php echo $course; ?>" 
                                       <?php echo in_array($course, $user_courses) ? 'checked' : ''; ?> 
                                       class="mr-2">
                                <?php echo ucfirst($course); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-6">
                    <a href="admindashboard.php" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>