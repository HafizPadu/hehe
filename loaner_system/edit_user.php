<?php
session_start();
// After fetching user:
if ($_SESSION['role'] !== 'super_admin' && $user['role'] === 'super_admin') {
    echo "❌ You are not authorized to edit a Super Admin.";
    exit;
}


include 'config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: manage_users.php'); // or your users listing page
    exit;
}

$message = '';

// Get current user data
$stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }

   if ($stmt->execute()) {
    $message = "<div class='message success'>✅ User updated successfully!</div>";

    // Re-fetch updated user data
    $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    $message = "<div class='message error'>❌ Failed to update user.</div>";
}

}
?>

<!-- Basic styling (reuse your current CSS or link to existing) -->
<style>
    body { 
        font-family: 'Segoe UI', sans-serif; 
        background: #f4f6f9; padding: 40px; 
    }

    .form-container { 
        max-width: 500px; 
        margin: auto; background: #fff; 
        padding: 30px; 
        border-radius: 10px; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    h2 {
        margin-bottom: 20px; 
    }

    input, select { 
        width: 100%; 
        padding: 12px; 
        margin: 10px 0; 
        border: 1px solid #ccc; 
        border-radius: 6px; }
    button { 
        padding: 12px; background: #007BFF; color: white; border: none; border-radius: 6px; cursor: pointer; width: 100%; 
    }

    button:hover { 
        background: #0056b3; 
    }

    .back-button {
    display: inline-block;
    margin-bottom: 20px;
    text-decoration: none;
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    }
    .back-button:hover {
        background: #5a6268;
    }

    .message { 
        padding: 15px; 
        border-radius: 6px; 
        margin-bottom: 15px; 
    }

    .success { 
        background: #d4edda; 
        color: #155724; 
        border-left: 5px solid #28a745; 
    }

    .error { 
        background: #f8d7da; 
        color: #721c24; 
        border-left: 5px solid #dc3545; 
        }

</style>

<div class="form-container">
    <a href="admin_users.php" class="back-button">⬅️ Back to Manage Users</a>

    <h2>Edit User</h2>
    <?= $message ?>
    <form method="post">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>New Password <small>(leave blank to keep current password)</small></label>
        <input type="password" name="password">

        <label>Role</label>
        <select name="role">
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
        </select>

        <button type="submit">💾 Save Changes</button>
    </form>
</div>
