<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

// Redirect to registration if no user data
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Registration System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-100 to-purple-200 min-h-screen">
    <div class="container mx-auto py-10 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-purple-700">User Dashboard</h1>
                <!-- <a href="logout.php" class="bg-red-500 text-white py-2 px-4 rounded-xl hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 transition">
                    Logout
                </a> -->
                <!-- In your userdashboard.php file, add this edit button (usually near the logout button) -->
<div class="flex space-x-4">
    <a href="useredit.php" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
        <i class="fas fa-edit mr-2"></i>Edit Profile
    </a>
    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">
        <i class="fas fa-sign-out-alt mr-2"></i>Logout
    </a>
</div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-8">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="md:w-1/3">
                            <div class="bg-purple-100 rounded-xl overflow-hidden shadow">
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="w-full h-auto object-cover">
                            </div>
                        </div>
                        
                        <div class="md:w-2/3">
                            <h2 class="text-2xl font-bold text-purple-700 mb-6">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-600">Username</h3>
                                    <p class="text-gray-800"><?php echo htmlspecialchars($user['user_name']); ?></p>
                                </div>
                                
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-600">Role</h3>
                                    <p class="text-gray-800"><?php echo htmlspecialchars($user['role']); ?></p>
                                </div>
                                
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-600">Age</h3>
                                    <p class="text-gray-800"><?php echo htmlspecialchars($user['age']); ?></p>
                                </div>
                                
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-600">Education</h3>
                                    <p class="text-gray-800"><?php echo htmlspecialchars($user['education']); ?></p>
                                </div>
                                
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-600">Phone Number</h3>
                                    <p class="text-gray-800"><?php echo htmlspecialchars($user['phone']); ?></p>
                                </div>
                                
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-600">Courses</h3>
                                    <p class="text-gray-800">
                                        <?php 
                                        if (!empty($user['courses'])) {
                                            echo htmlspecialchars(implode(', ', $user['courses']));
                                        } else {
                                            echo 'No courses selected';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  
</body>
</html>
