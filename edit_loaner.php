<?php
include 'includes/header.php';
include 'config.php';
include 'includes/sidebar.php';

// Initialize variables
$loaner_id = intval($_GET['id'] ?? 0);
$message = null;
$loaner = null;

// Validate ID
if ($loaner_id <= 0) {
    $message = ["type" => "error", "text" => "Invalid loaner ID."];
    include 'includes/footer.php';
    exit;
}

// Fetch current data with error handling
try {
    $stmt = $conn->prepare("SELECT * FROM loaners WHERE id = ?");
    $stmt->bind_param("i", $loaner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loaner = $result->fetch_assoc();
    
    if (!$loaner) {
        $message = ["type" => "error", "text" => "Loaner not found."];
        include 'includes/footer.php';
        exit;
    }
} catch (Exception $e) {
    $message = ["type" => "error", "text" => "Database error: " . $e->getMessage()];
    include 'includes/footer.php';
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_loaner'])) {
    $name = trim($_POST['loaner_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // Validate inputs
    if (empty($name) || empty($location) || empty($department)) {
        $message = ["type" => "error", "text" => "All fields are required."];
    } elseif (strlen($name) > 100 || strlen($location) > 100 || strlen($department) > 100) {
        $message = ["type" => "error", "text" => "Fields must be less than 100 characters."];
    } else {
        try {
            // Check if name already exists (excluding current record)
            $check_stmt = $conn->prepare("SELECT id FROM loaners WHERE loaner_name = ? AND id != ?");
            $check_stmt->bind_param("si", $name, $loaner_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $message = ["type" => "error", "text" => "A loaner with this name already exists."];
            } else {
                // Update record
                $update_stmt = $conn->prepare("UPDATE loaners SET loaner_name = ?, location = ?, department = ? WHERE id = ?");
                $update_stmt->bind_param("sssi", $name, $location, $department, $loaner_id);

                if ($update_stmt->execute()) {
                    $message = ["type" => "success", "text" => "Loaner updated successfully!"];
                    // Update local data to reflect changes
                    $loaner['loaner_name'] = $name;
                    $loaner['location'] = $location;
                    $loaner['department'] = $department;
                } else {
                    $message = ["type" => "error", "text" => "Failed to update loaner: " . $conn->error];
                }
            }
        } catch (Exception $e) {
            $message = ["type" => "error", "text" => "Database error: " . $e->getMessage()];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Loaner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content {
            padding: 30px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 100%;
            box-sizing: border-box
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-form {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message i {
            font-size: 20px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        @media (max-width: 576px) {
            .content {
                padding: 20px;
            }
            
            .edit-form {
                padding: 15px;
            }
            
            .form-actions {
                flex-direction: column-reverse;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <h2><i class="fas fa-edit"></i> Edit Loaner</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message['type']; ?>">
                <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message['text']); ?></span>
            </div>
        <?php endif; ?>

        <div class="edit-form">
            <form method="post">
                <div class="form-group">
                    <label for="loaner_name"><i class="fas fa-user-tag"></i> Loaner Name</label>
                    <input type="text" id="loaner_name" name="loaner_name" 
                           value="<?php echo htmlspecialchars($loaner['loaner_name']); ?>" 
                           placeholder="Enter loaner name" required>
                </div>

                <div class="form-group">
                    <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($loaner['location']); ?>" 
                           placeholder="Enter location" required>
                </div>

                <div class="form-group">
                    <label for="department"><i class="fas fa-building"></i> Department</label>
                    <input type="text" id="department" name="department" 
                           value="<?php echo htmlspecialchars($loaner['department']); ?>" 
                           placeholder="Enter department" required>
                </div>

                <div class="form-actions">
                    <a href="manage_loaner.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" name="update_loaner" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Loaner
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
