<?php
session_start();
require 'database.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];

    $db = new Database();
    $conn = $db->connect();

    // Get user data first to delete profile picture
    $stmt = $conn->prepare("SELECT profile_picture FROM user_details WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user && !empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
        unlink($user['profile_picture']);
    }

    // Delete user from database
    $stmt = $conn->prepare("DELETE FROM user_details WHERE id = ?");
    $stmt->execute([$userId]);

    $_SESSION['message'] = 'User deleted successfully';
    header("Location: admindashboard.php");
    exit;
}


// Get all users from database

$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT * FROM user_details ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 ml-3">User Management</h1>
            <div class="flex gap-4">
                
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded action-btn mr-3">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Status Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg">
                <?php echo $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profile</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $user['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="h-10 w-10 rounded-full overflow-hidden border-2 border-gray-200">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <div class="h-full w-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['user_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $user['role'] === 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <!-- In your table row where you display users, modify the actions column: -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <!-- Edit Button -->
                                    <a href="adminedit.php?id=<?php echo $user['id']; ?>" 
                                    class="text-blue-600 hover:text-blue-900 action-btn"
                                    title="Edit User">
                                        <i class="fas fa-edit fa-lg"></i>
                                    </a>
                                    
                                    <!-- Delete Button -->
                                    <a href="admindashboard.php?delete=<?php echo $user['id']; ?>" 
                                    class="text-red-600 hover:text-red-900 action-btn"
                                    title="Delete User"
                                    onclick="return confirm('Are you sure you want to permanently delete this user?');">
                                        <i class="fas fa-trash-alt fa-lg"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Enhanced confirmation for delete
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('a[href*="delete"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this user?\n\nThis will permanently remove their account and profile data.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
