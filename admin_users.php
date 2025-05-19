<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Role-based access control
if ($_SESSION['role'] === 'viewer') {
    echo '<div class="content">';
    echo '<div class="message error">🚫 Access Denied: You do not have permission to view this page. Redirecting back...</div>';
    echo '<script>setTimeout(function(){ window.history.back(); }, 3000);</script>';
    echo '</div>';
    exit;
} elseif ($_SESSION['role'] === 'admin' && !isset($_GET['admin_page'])) {
    echo '<div class="content">';
    echo '<div class="message error">🚫 Access Denied: Admin access required. Redirecting back...</div>';
    echo '<script>setTimeout(function(){ window.history.back(); }, 3000);</script>';
    echo '</div>';
    exit;
}

include 'config.php';

$message = '';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Sorting
$sortField = in_array($_GET['sort'] ?? '', ['username', 'role', 'status']) ? $_GET['sort'] : 'id';
$sortOrder = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = $conn->real_escape_string($_POST['username']);
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);
    $status = 'active';
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $exists = $conn->query("SELECT * FROM users WHERE username = '$username' OR email = '$email'");
    if ($exists->num_rows > 0) {
        $message = "<div class='message error'>❌ Username or email already exists.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $fullname, $email, $password, $role, $status);
        if ($stmt->execute()) {
            $message = "<div class='message success'>✅ User created successfully!</div>";
        } else {
            $message = "<div class='message error'>❌ Error creating user: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id !== $_SESSION['user_id']) {
        // Prevent super admin deletion
        $result = $conn->query("SELECT role FROM users WHERE id = $id");
        if ($result && $row = $result->fetch_assoc() && $row['role'] === 'super_admin') {
            $message = "<div class='message error'>❌ You cannot delete a Super Admin.</div>";
        } else {
            $conn->query("DELETE FROM users WHERE id = $id");
            $message = "<div class='message success'>🗑️ User deleted successfully.</div>";
        }
    } else {
        $message = "<div class='message error'>❌ You cannot delete yourself.</div>";
    }
}

// Handle deactivate/activate
if (isset($_GET['toggle_status'])) {
    $id = (int) $_GET['toggle_status'];
    $result = $conn->query("SELECT status FROM users WHERE id = $id");
    if ($result && $row = $result->fetch_assoc()) {
        $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
        $conn->query("UPDATE users SET status = '$newStatus' WHERE id = $id");
        $message = "<div class='message success'>🔄 User status updated to $newStatus.</div>";
    }
}

// Filtering
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = "WHERE 1";
if ($search !== '') $where .= " AND (username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%')";
if ($roleFilter !== '') $where .= " AND role = '$roleFilter'";
if ($statusFilter !== '') $where .= " AND status = '$statusFilter'";

// Count for pagination
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users $where")->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Fetch users
$query = "SELECT * FROM users $where ORDER BY $sortField $sortOrder LIMIT $limit OFFSET $offset";
$users = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Loaner System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #4361ee;
        --primary-dark: #3a56d4;
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --danger: #f72585;
        --warning: #f8961e;
        --info: #4895ef;
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --white: #ffffff;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s ease;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        color: var(--dark);
        line-height: 1.6;
    }

    .content {
        padding: 2rem;
        margin-left: 250px;
        transition: var(--transition);
    }

    .sidebar.collapsed ~ .content {
        margin-left: 70px;
    }

    h2 {
        font-size: 1.75rem;
        color: var(--dark);
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    h2 i {
        color: var(--primary);
    }

    /* Card Styles */
    .card {
        background: var(--white);
        border-radius: 0.5rem;
        box-shadow: var(--shadow);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Form Styles */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--dark);
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        transition: var(--transition);
        background-color: var(--white);
    }

    .form-control:focus {
        border-color: var(--primary);
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.5;
        text-align: center;
        text-decoration: none;
        white-space: nowrap;
        vertical-align: middle;
        cursor: pointer;
        user-select: none;
        border: 1px solid transparent;
        border-radius: 0.375rem;
        transition: var(--transition);
    }

    .btn-primary {
        color: var(--white);
        background-color: var(--primary);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
    }

    .btn-success {
        color: var(--white);
        background-color: var(--success);
    }

    .btn-danger {
        color: var(--white);
        background-color: var(--danger);
    }

    .btn-warning {
        color: var(--white);
        background-color: var(--warning);
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .btn i {
        margin-right: 0.5rem;
    }

    /* Message Styles */
    .message {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 0.375rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .message.success {
        background-color: rgba(76, 201, 240, 0.15);
        color: #0c5460;
        border-left: 4px solid var(--success);
    }

    .message.error {
        background-color: rgba(247, 37, 133, 0.15);
        color: #721c24;
        border-left: 4px solid var(--danger);
    }

    /* Filter Styles */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: center;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Table Styles */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .table th,
    .table td {
        padding: 1rem;
        text-align: left;
        vertical-align: middle;
        border-bottom: 1px solid #dee2e6;
    }

    .table th {
        background-color: var(--primary);
        color: var(--white);
        font-weight: 600;
        position: sticky;
        top: 0;
    }

    .table tr:nth-child(even) td {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .table tr:hover td {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .sort-link {
        color: var(--white);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .sort-link:hover {
        color: var(--white);
        text-decoration: underline;
    }

    /* Badge Styles */
    .badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .badge-primary {
        color: var(--white);
        background-color: var(--primary);
    }

    .badge-success {
        color: var(--white);
        background-color: #28a745;
    }

    .badge-warning {
        color: var(--dark);
        background-color: var(--warning);
    }

    .badge-danger {
        color: var(--white);
        background-color: var(--danger);
    }

    .badge-info {
        color: var(--white);
        background-color: var(--info);
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    /* Password Strength Meter */
    .password-strength {
        margin-top: 0.5rem;
        height: 0.25rem;
        background-color: var(--light-gray);
        border-radius: 0.125rem;
        overflow: hidden;
    }

    .strength-meter {
        height: 100%;
        width: 0;
        transition: var(--transition);
    }

    .strength-weak {
        background-color: var(--danger);
        width: 33%;
    }

    .strength-medium {
        background-color: var(--warning);
        width: 66%;
    }

    .strength-strong {
        background-color: #28a745;
        width: 100%;
    }

    .strength-text {
        font-size: 0.75rem;
        margin-top: 0.25rem;
        font-weight: 500;
    }

    /* Pagination */
    .pagination {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.25rem;
        background-color: var(--white);
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        border: 1px solid #dee2e6;
    }

    .page-link:hover,
    .page-link.active {
        background-color: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .content {
            padding: 1rem;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .content {
            margin-left: 70px;
        }

        .sidebar.collapsed ~ .content {
            margin-left: 70px;
        }

        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-group {
            width: 100%;
        }

        .table th,
        .table td {
            padding: 0.75rem;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
    </style>
</head>
<body>

<!-- Your existing sidebar (included from separate file) -->
<?php include 'includes/sidebar.php'; ?>

<div class="content">
    <?php if ($message): ?>
        <div><?= $message ?></div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-plus"></i> Add New User</h2>
        <form method="post" onsubmit="return validateForm()">
            <input type="hidden" name="action" value="add_user">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" name="username" id="username" required>
                </div>
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" class="form-control" name="fullname" id="fullname" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select class="form-control" name="role" id="role" required>
                        <option value="viewer">Viewer</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" name="password" id="password" required 
                           onkeyup="checkPasswordStrength(this.value)">
                    <div class="password-strength">
                        <div class="strength-meter" id="strengthMeter"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Create User
            </button>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-users-cog"></i> Manage Users</h2>
        
        <div class="filter-bar">
            <div class="filter-group">
                <input type="text" class="form-control" name="search" placeholder="Search users..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
                <select class="form-control" name="role">
                    <option value="">All Roles</option>
                    <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="viewer" <?= $roleFilter === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                </select>
            </div>
            <div class="filter-group">
                <select class="form-control" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="?" class="btn btn-warning">
                <i class="fas fa-sync-alt"></i> Reset
            </a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>
                            <a href="?sort=username&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" class="sort-link">
                                Username
                                <i class="fas fa-sort<?= $sortField === 'username' ? ('-' . strtolower($sortOrder)) : '' ?>"></i>
                            </a>
                        </th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>
                            <a href="?sort=role&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" class="sort-link">
                                Role
                                <i class="fas fa-sort<?= $sortField === 'role' ? ('-' . strtolower($sortOrder)) : '' ?>"></i>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=status&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>" class="sort-link">
                                Status
                                <i class="fas fa-sort<?= $sortField === 'status' ? ('-' . strtolower($sortOrder)) : '' ?>"></i>
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows > 0): ?>
                        <?php $i = $offset + 1; while ($row = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= $row['role'] === 'super_admin' ? 'badge-danger' : 
                                           ($row['role'] === 'admin' ? 'badge-primary' : 'badge-info') ?>">
                                        <?= ucfirst($row['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?= $row['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($_SESSION['role'] !== 'super_admin' || $row['role'] !== 'super_admin'): ?>
                                            <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?toggle_status=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Toggle Status" 
                                           onclick="return confirm('Are you sure you want to toggle this user\'s status?')">
                                            <i class="fas fa-power-off"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="page-link" title="Previous">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($p = max(1, $page - 2); $p <= min($page + 2, $totalPages); $p++): ?>
                <a href="?page=<?= $p ?>" class="page-link <?= $p == $page ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="page-link" title="Next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function checkPasswordStrength(password) {
    const strengthMeter = document.getElementById("strengthMeter");
    const strengthText = document.getElementById("strengthText");
    let strength = 0;
    
    // Reset classes
    strengthMeter.className = 'strength-meter';
    
    // Check length
    if (password.length > 7) strength++;
    
    // Check for mixed case
    if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength++;
    
    // Check for numbers
    if (password.match(/([0-9])/)) strength++;
    
    // Check for special chars
    if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength++;
    
    // Update meter and text
    switch(strength) {
        case 0:
            strengthText.textContent = "Very Weak";
            strengthText.style.color = "#dc3545";
            strengthMeter.classList.add('strength-weak');
            break;
        case 1:
            strengthText.textContent = "Weak";
            strengthText.style.color = "#fd7e14";
            strengthMeter.classList.add('strength-weak');
            break;
        case 2:
            strengthText.textContent = "Moderate";
            strengthText.style.color = "#ffc107";
            strengthMeter.classList.add('strength-medium');
            break;
        case 3:
            strengthText.textContent = "Strong";
            strengthText.style.color = "#28a745";
            strengthMeter.classList.add('strength-strong');
            break;
        case 4:
            strengthText.textContent = "Very Strong";
            strengthText.style.color = "#20c997";
            strengthMeter.classList.add('strength-strong');
            break;
    }
}

function validateForm() {
    const password = document.getElementById("password").value;
    if (password.length < 6) {
        alert("Password must be at least 6 characters long!");
        return false;
    }
    return true;
}
</script>
</body>
</html>