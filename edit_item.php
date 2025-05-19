<?php
session_start();
if ($_SESSION['role'] === 'viewer') {
    exit();
}
?>

<?php
ob_start();
include 'includes/header.php';
include 'config.php';
include 'includes/sidebar.php';

// Check if user is logged in and has permission to edit items
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate and sanitize the ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='message error'>❌ Invalid item identifier.</div>";
    exit;
}

$id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Prepare and execute the query with error handling
try {
    $stmt = $conn->prepare("SELECT * FROM items WHERE serial_number = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if (!$item) {
        echo "<div class='message error'>❌ Item not found.</div>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='message error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    // Validate and sanitize input
    $allowed_types = ['PC', 'MONITOR', 'NOTEBOOK', 'PRINTER'];
    $type = isset($_POST['item_type']) && in_array($_POST['item_type'], $allowed_types) 
             ? $_POST['item_type'] 
             : null;
    $name = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
    
    if (!$type || empty($name)) {
        echo "<div class='message error'>❌ Please fill all fields with valid data.</div>";
    } else {
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        
        try {
            $update = $conn->prepare("UPDATE items SET item = ?, model = ? WHERE serial_number = ?");
            if (!$update) {
                throw new Exception("Database error: " . $conn->error);
            }
            $update->bind_param("ssi", $type, $name, $id);
            
            if ($update->execute()) {
                $_SESSION['flash_message'] = "✅ Item updated successfully!";
                header("Location: manage_items.php");
                exit();
            } else {
                throw new Exception("Failed to update item.");
            }
        } catch (Exception $e) {
            echo "<div class='message error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Inventory System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --success-color: #4bb543;
            --error-color: #f44336;
            --text-color: #333;
            --light-gray: #f5f7fa;
            --border-color: #ddd;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 60px);
        }
        
        .form-container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px;
            box-sizing: border-box;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        h2 {
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            font-size: 1.5rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        select, input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            font-size: 1rem;
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .error {
            background-color: #ffebee;
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .success {
            background-color: #e8f5e9;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .form-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Edit Item</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="item_type"><i class="fas fa-tag"></i> Item Type</label>
                    <select name="item_type" id="item_type" required>
                        <option value="">-- Select Item Type --</option>
                        <option value="PC" <?= $item['item'] === 'PC' ? 'selected' : '' ?>>PC</option>
                        <option value="MONITOR" <?= $item['item'] === 'MONITOR' ? 'selected' : '' ?>>Monitor</option>
                        <option value="NOTEBOOK" <?= $item['item'] === 'NOTEBOOK' ? 'selected' : '' ?>>Notebook</option>
                        <option value="PRINTER" <?= $item['item'] === 'PRINTER' ? 'selected' : '' ?>>Printer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="item_name"><i class="fas fa-keyboard"></i> Model Name</label>
                    <input type="text" name="item_name" id="item_name" 
                           value="<?= htmlspecialchars($item['model']) ?>" 
                           placeholder="Enter model name" required>
                </div>
                
                <button type="submit" name="update_item" class="btn">
                    <i class="fas fa-save"></i> Update Item
                </button>
                
                <a href="manage_items.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Items List
                </a>
            </form>
        </div>
    </div>
</body>
</html>