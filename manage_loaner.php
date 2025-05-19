<?php
include 'includes/header.php';
include 'config.php';
include 'includes/sidebar.php';

// Check user role - assuming this is stored in session
$isViewer = isset($_SESSION['role']) && $_SESSION['role'] === 'viewer'; // Adjust based on your role system

// Initialize message variable
$message = null;

// Handle add loaner form - only if not viewer
if (!$isViewer && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_loaner'])) {
    $loaner_name = trim($_POST['loaner_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // Validate inputs
    if (empty($loaner_name) || empty($location) || empty($department)) {
        $message = ["type" => "error", "text" => "Please fill in all fields."];
    } elseif (strlen($loaner_name) > 100 || strlen($location) > 100 || strlen($department) > 100) {
        $message = ["type" => "error", "text" => "Inputs must be less than 100 characters."];
    } else {
        // Check if loaner already exists
        $check_stmt = $conn->prepare("SELECT id FROM loaners WHERE loaner_name = ?");
        $check_stmt->bind_param("s", $loaner_name);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = ["type" => "error", "text" => "Loaner with this name already exists."];
        } else {
            // Insert new loaner
            $stmt = $conn->prepare("INSERT INTO loaners (loaner_name, location, department) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $loaner_name, $location, $department);
            
            if ($stmt->execute()) {
                $message = ["type" => "success", "text" => "Loaner added successfully."];
                // Clear form fields after successful submission
                $_POST = [];
            } else {
                $message = ["type" => "error", "text" => "Failed to add loaner: " . $conn->error];
            }
        }
    }
}

// Handle delete - only if not viewer
if (!$isViewer && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_loaner'])) {
    $delete_id = intval($_POST['delete_id']);

    if ($delete_id <= 0) {
        $message = ["type" => "error", "text" => "Invalid loaner ID."];
    } else {
        // Check if loaner is in use
        $check = $conn->prepare("SELECT COUNT(*) as count FROM loans WHERE loaner_id = ?");
        $check->bind_param("i", $delete_id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $message = ["type" => "error", "text" => "Cannot delete: Loaner is currently in use."];
        } else {
            $stmt = $conn->prepare("DELETE FROM loaners WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            
            if ($stmt->execute()) {
                $message = ["type" => "success", "text" => "Loaner deleted successfully."];
            } else {
                $message = ["type" => "error", "text" => "Failed to delete loaner: " . $conn->error];
            }
        }
    }
}
?>


<style>
    .content {
        padding: 30px;
        font-family: 'Segoe UI', sans-serif;
        width: 100%;
        box-sizing: border-box;
    }

    h2 {
        font-size: 26px;
        margin-bottom: 20px;
        border-bottom: 2px solid #007BFF;
        display: inline-block;
        padding-bottom: 5px;
        color: #333;
    }

    .form-section {
        margin-bottom: 30px;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
        align-items: flex-end;
    }

    .form-group {
        flex: 1;
        min-width: 200px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #495057;
    }

    input[type="text"] {
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        width: 100%;
        font-size: 14px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    input[type="text"]:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    button, .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    button:hover, .btn:hover {
        transform: translateY(-1px);
    }

    .btn-primary {
        background-color: #007BFF;
        color: white;
    }

    .btn-primary:hover {
        background-color: #0069d9;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c82333;
    }

    .btn-warning {
        background-color: #ffc107;
        color: #212529;
    }

    .btn-warning:hover {
        background-color: #e0a800;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    table th, table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }

    table th {
        background-color: #007BFF;
        color: white;
        font-weight: 600;
    }

    table tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    table tr:hover {
        background-color: #e9ecef;
    }

    .message {
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .message i {
        font-size: 18px;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border-left: 5px solid #28a745;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 5px solid #dc3545;
    }

    .actions-cell {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .no-data {
        text-align: center;
        padding: 30px;
        color: #6c757d;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
        }
        
        .form-group {
            width: 100%;
        }
        
        table {
            display: block;
            overflow-x: auto;
        }
        
        .actions-cell {
            flex-direction: column;
        }
    }
</style>

<div class="content">
    <h2><i class=""></i> Manage Loaners</h2>

    <?php if ($message): ?>
        <div class="message <?php echo $message['type']; ?>">
            <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>

    <!-- Add Loaner Form - only show if not viewer -->
    <?php if (!$isViewer): ?>
    <div class="form-section">
        <h3><i class="fas fa-plus-circle"></i> Add New Loaner</h3>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="loaner_name">Loaner Name</label>
                    <input type="text" id="loaner_name" name="loaner_name" 
                           value="<?php echo htmlspecialchars($_POST['loaner_name'] ?? ''); ?>" 
                           placeholder="Enter loaner name" required>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                           placeholder="Enter location" required>
                </div>
                
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" 
                           value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" 
                           placeholder="Enter department" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_loaner" class="btn-primary">
                        <i class="fas fa-save"></i> Add Loaner
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Loaners Table -->
    <h3><i class="fas fa-list"></i> Loaner List</h3>
    <table>
        <thead>
            <tr>
                <th>Loaner Name</th>
                <th>Location</th>
                <th>Department</th>
                <?php if (!$isViewer): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT * FROM loaners ORDER BY loaner_name ASC");
            
            if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="<?php echo $isViewer ? 3 : 4; ?>" class="no-data">No loaners found.</td>
                </tr>
            <?php else:
                while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['loaner_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <?php if (!$isViewer): ?>
                    <td class="actions-cell">
                        <a href="edit_loaner.php?id=<?php echo $row['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this loaner?');" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_loaner" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile;
            endif; ?>
        </tbody>
    </table>
</div>