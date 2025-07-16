<?php
session_start();
require_once 'database.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $first_name = trim(htmlspecialchars($_POST['first_name'] ?? ''));
    $last_name = trim(htmlspecialchars($_POST['last_name'] ?? ''));
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
    $age = trim(htmlspecialchars($_POST['age'] ?? ''));
    $education = $_POST['education'] ?? '';
    $courses = $_POST['courses'] ?? [];

    // Validate inputs (similar to registration)
    if (empty($first_name) || !preg_match("/^[a-zA-Z ]{2,50}$/", $first_name)) {
        $errors[] = "First name must be 2-50 letters only.";
    }
    // Add other validations...

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
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare(
            "UPDATE user_details SET 
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                age = :age,
                education = :education,
                courses = :courses,
                profile_picture = :profile_picture
             WHERE id = :id"
        );
        
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'age' => $age,
            'education' => $education,
            'courses' => implode(',', $courses),
            'profile_picture' => $profile_picture,
            'id' => $user['id']
        ]);
        
        // Update session data
        $_SESSION['user'] = [
            'id' => $user['id'],
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_name' => $user['user_name'], // username shouldn't change
            'email' => $email,
            'phone' => $phone,
            'age' => $age,
            'education' => $education,
            'courses' => $courses,
            'role' => $user['role'], // role shouldn't change
            'profile_picture' => $profile_picture
        ];
        
        $success = "Profile updated successfully!";
        header("Location: userdashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Your Profile</h1>
            
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
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <!-- Profile Picture -->
                <div class="flex items-center space-x-4">
                    <div class="h-20 w-20 rounded-full overflow-hidden border-2 border-gray-200">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="h-full w-full object-cover">
                        <?php else: ?>
                            <div class="h-full w-full bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 text-2xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-1">Change Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*" class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100">
                    </div>
                </div>
                
                <!-- Personal Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-gray-700">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                               class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label for="last_name" class="block text-gray-700">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                               class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <label for="email" class="block text-gray-700">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="phone" class="block text-gray-700">Phone</label>
                        <input type="text" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>" 
                               class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label for="age" class="block text-gray-700">Age</label>
                        <input type="number" id="age" name="age" 
                               value="<?php echo htmlspecialchars($user['age']); ?>" 
                               class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                
                <!-- Education -->
                <div>
                    <label class="block text-gray-700 mb-1">Current Education</label>
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
                
                <!-- Courses -->
                <div>
                    <label class="block text-gray-700 mb-1">Courses</label>
                    <div class="flex flex-wrap gap-4">
                        <?php $course_options = ['php', 'angular', 'python', 'react']; ?>
                        <?php $user_courses = is_array($user['courses']) ? $user['courses'] : explode(',', $user['courses'] ?? ''); ?>
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
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="userdashboard.php" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400">
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