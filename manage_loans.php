<?php
ob_start();
session_start();

include 'includes/header.php';
include 'config.php';
include 'includes/sidebar.php';

// Check user role
$isViewer = isset($_SESSION['role']) && $_SESSION['role'] === 'viewer';

// Initialize variables
$filter_status = '';
$search = '';
$error = '';
$success = '';

// Pagination configuration
$per_page = 10; // Items per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Process filters
if (isset($_GET['status']) && in_array($_GET['status'], ['loaned', 'returned'])) {
    $filter_status = $_GET['status'];
}

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
}

// Build main query with pagination
$query = "SELECT SQL_CALC_FOUND_ROWS l.*, i.model FROM loans l 
          LEFT JOIN items i ON l.serial_number = i.serial_number 
          WHERE 1=1";
$params = [];
$types = "";

// Add filters to query
if (!empty($filter_status)) {
    $query .= " AND l.status = ?";
    $types .= "s";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $query .= " AND (l.serial_number LIKE ? OR l.loaner_name LIKE ? OR l.location LIKE ?)";
    $types .= "sss";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY l.start_date DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $per_page;
$params[] = $offset;

// Execute main query
$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$loans = $stmt->get_result();

// Get total count for pagination
$total_result = $conn->query("SELECT FOUND_ROWS() as total");
$total_row = $total_result->fetch_assoc();
$total_loans = $total_row['total'];
$total_pages = ceil($total_loans / $per_page);

// Only fetch additional data if not viewer
if (!$isViewer) {
    $available_items = $conn->query("SELECT serial_number, model FROM items WHERE status = 'available' ORDER BY model");
    if (!$available_items) {
        $error = "Failed to load available items: " . $conn->error;
    }

    $loaners = $conn->query("SELECT * FROM loaners ORDER BY loaner_name");
    if (!$loaners) {
        $error = "Failed to load loaner information: " . $conn->error;
    }
}

// Handle loan form submission (admin only)
if (!$isViewer && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_loan'])) {
    $serial_number = filter_input(INPUT_POST, 'serial_number', FILTER_SANITIZE_STRING);
    $loaner_id = filter_input(INPUT_POST, 'loaner_name', FILTER_VALIDATE_INT);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $return_date = filter_input(INPUT_POST, 'return_date', FILTER_SANITIZE_STRING);
    
    // Validate dates
    if (!strtotime($start_date) || !strtotime($return_date)) {
        $error = "Invalid date format";
    } elseif (strtotime($return_date) < strtotime($start_date)) {
        $error = "Return date must be after start date";
    }
    
    if (empty($error)) {
        try {
            $conn->begin_transaction();
            
            // Get loaner details
            $stmt_loaner = $conn->prepare("SELECT loaner_name, department FROM loaners WHERE id = ?");
            $stmt_loaner->bind_param("i", $loaner_id);
            $stmt_loaner->execute();
            $loaner = $stmt_loaner->get_result()->fetch_assoc();
            
            if (!$loaner) {
                throw new Exception("Invalid loaner selected");
            }
            
            // Insert loan record
            $stmt_insert = $conn->prepare("INSERT INTO loans (serial_number, loaner_name, location, department, start_date, return_date, status) VALUES (?, ?, ?, ?, ?, ?, 'loaned')");
            $stmt_insert->bind_param("ssssss", 
                $serial_number,
                $loaner['loaner_name'],
                $location,
                $department,
                $start_date,
                $return_date
            );
            $stmt_insert->execute();
            
            // Update item status
            $stmt_update = $conn->prepare("UPDATE items SET status = 'loaned' WHERE serial_number = ?");
            $stmt_update->bind_param("s", $serial_number);
            $stmt_update->execute();
            
            $conn->commit();
            $success = "Loan created successfully!";
            header("Location: manage_loans.php?success=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error creating loan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Loans</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .form-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .loan-form, .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        input, select {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        button, .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #007BFF;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .message {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f4f4f4;
            position: sticky;
            top: 0;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .action-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
            background-color: rgba(40, 167, 69, 0.1);
        }
        .action-link:hover {
            text-decoration: underline;
            background-color: rgba(40, 167, 69, 0.2);
        }
        .status-loaned {
            color: #dc3545;
            font-weight: 600;
        }
        .status-returned {
            color: #28a745;
            font-weight: 600;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007BFF;
        }
        .pagination a:hover {
            background-color: #f1f1f1;
        }
        .pagination .active {
            background-color: #007BFF;
            color: white;
            border-color: #007BFF;
        }
        .pagination .disabled {
            color: #6c757d;
            pointer-events: none;
        }
        .page-info {
            margin-top: 10px;
            font-size: 0.9em;
            color: #6c757d;
            width: 100%;
            text-align: center;
        }
        @media (max-width: 768px) {
            .loan-form, .filter-form {
                grid-template-columns: 1fr;
            }
            .pagination {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <h2><i class="fas fa-exchange-alt"></i> Manage Loans</h2>

        <?php if (!empty($success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Add Loan Form - Admin Only -->
        <?php if (!$isViewer): ?>
        <div class="form-container">
            <h3><i class="fas fa-plus-circle"></i> Create New Loan</h3>
            <form method="post" class="loan-form">
                <div class="form-group">
                    <label for="serial_number">Item</label>
                    <select id="serial_number" name="serial_number" required>
                        <option value="">-- Select Available Item --</option>
                        <?php if ($available_items && $available_items->num_rows > 0): ?>
                            <?php while ($row = $available_items->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($row['serial_number']); ?>">
                                    <?php echo htmlspecialchars($row['serial_number']) . ' - ' . htmlspecialchars($row['model']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No available items</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="loaner_name">Loaner</label>
                    <select id="loaner_name" name="loaner_name" required>
                        <option value="">-- Select Loaner --</option>
                        <?php if ($loaners && $loaners->num_rows > 0): ?>
                            <?php $loaners->data_seek(0); while ($loaner = $loaners->fetch_assoc()): ?>
                                <option value="<?php echo $loaner['id']; ?>" 
                                        data-location="<?php echo htmlspecialchars($loaner['location']); ?>"
                                        data-department="<?php echo htmlspecialchars($loaner['department']); ?>">
                                    <?php echo htmlspecialchars($loaner['loaner_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No loaners available</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <select id="location" name="location" required>
                        <option value="">-- Select Location --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" required>
                        <option value="">-- Select Department --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label for="return_date">Return Date</label>
                    <input type="date" id="return_date" name="return_date" required>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" name="create_loan" class="btn-primary">
                        <i class="fas fa-save"></i> Create Loan
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Filter Form - Visible to All -->
        <div class="form-container">
            <h3><i class="fas fa-filter"></i> Filter Loans</h3>
            <form method="get" class="filter-form">
                <input type="hidden" name="page" value="1">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" placeholder="Item, loaner, or location" 
                           value="<?php echo htmlspecialchars($search); ?>" <?php echo $isViewer ? 'readonly' : ''; ?>>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" <?php echo $isViewer ? 'disabled' : ''; ?>>
                        <option value="">All Statuses</option>
                        <option value="loaned" <?php echo $filter_status === 'loaned' ? 'selected' : ''; ?>>Loaned</option>
                        <option value="returned" <?php echo $filter_status === 'returned' ? 'selected' : ''; ?>>Returned</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary" <?php echo $isViewer ? 'disabled' : ''; ?>>
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <?php if (!empty($filter_status) || !empty($search)): ?>
                        <a href="manage_loans.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Loans Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Loaner</th>
                        <th>Location</th>
                        <th>Department</th>
                        <th>Start Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <?php if (!$isViewer): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($loans && $loans->num_rows > 0): ?>
                        <?php while ($row = $loans->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row['serial_number']); ?>
                                    <?php if (!empty($row['model'])): ?>
                                        <br><small><?php echo htmlspecialchars($row['model']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['loaner_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['return_date'])); ?></td>
                                <td class="status-<?php echo htmlspecialchars($row['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                </td>
                                <?php if (!$isViewer): ?>
                                <td>
                                    <?php if ($row['status'] === 'loaned'): ?>
                                        <a class="action-link" href="mark_returned.php?id=<?php echo urlencode($row['SN']); ?>" 
                                           onclick="return confirm('Are you sure you want to mark this item as returned?');">
                                            <i class="fas fa-check"></i> Mark Returned
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $isViewer ? 7 : 8; ?>" style="text-align: center;">
                                No loans found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <!-- First Page -->
            <?php if ($page > 1): ?>
                <a href="?page=1<?php echo !empty($filter_status) ? '&status='.urlencode($filter_status) : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   class="btn-secondary">
                    <i class="fas fa-angle-double-left"></i> First
                </a>
            <?php else: ?>
                <span class="btn-secondary disabled">
                    <i class="fas fa-angle-double-left"></i> First
                </span>
            <?php endif; ?>

            <!-- Previous Page -->
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filter_status) ? '&status='.urlencode($filter_status) : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   class="btn-secondary">
                    <i class="fas fa-angle-left"></i> Prev
                </a>
            <?php else: ?>
                <span class="btn-secondary disabled">
                    <i class="fas fa-angle-left"></i> Prev
                </span>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php 
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <span class="btn-secondary disabled">...</span>
            <?php endif;
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="btn-primary active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($filter_status) ? '&status='.urlencode($filter_status) : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                       class="btn-secondary">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor;
            
            if ($end_page < $total_pages): ?>
                <span class="btn-secondary disabled">...</span>
            <?php endif; ?>

            <!-- Next Page -->
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filter_status) ? '&status='.urlencode($filter_status) : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   class="btn-secondary">
                    Next <i class="fas fa-angle-right"></i>
                </a>
            <?php else: ?>
                <span class="btn-secondary disabled">
                    Next <i class="fas fa-angle-right"></i>
                </span>
            <?php endif; ?>

            <!-- Last Page -->
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($filter_status) ? '&status='.urlencode($filter_status) : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   class="btn-secondary">
                    Last <i class="fas fa-angle-double-right"></i>
                </a>
            <?php else: ?>
                <span class="btn-secondary disabled">
                    Last <i class="fas fa-angle-double-right"></i>
                </span>
            <?php endif; ?>

            <div class="page-info">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_loans); ?> of <?php echo $total_loans; ?> loans
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$isViewer): ?>
    <script>
        // Dynamic population of location and department based on selected loaner
        document.getElementById('loaner_name').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const locationSelect = document.getElementById('location');
            const departmentSelect = document.getElementById('department');
            
            // Clear existing options
            locationSelect.innerHTML = '<option value="">-- Select Location --</option>';
            departmentSelect.innerHTML = '<option value="">-- Select Department --</option>';
            
            if (selectedOption.value) {
                // Add the selected loaner's location and department
                const location = selectedOption.getAttribute('data-location');
                const department = selectedOption.getAttribute('data-department');
                
                if (location) {
                    const option = document.createElement('option');
                    option.value = location;
                    option.textContent = location;
                    option.selected = true;
                    locationSelect.appendChild(option);
                }
                
                if (department) {
                    const option = document.createElement('option');
                    option.value = department;
                    option.textContent = department;
                    option.selected = true;
                    departmentSelect.appendChild(option);
                }
            }
        });
        
        // Set default dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            const nextWeekFormatted = nextWeek.toISOString().split('T')[0];
            
            document.getElementById('start_date').value = today;
            document.getElementById('return_date').value = nextWeekFormatted;
            document.getElementById('start_date').min = today;
            document.getElementById('return_date').min = today;
        });
    </script>
    <?php endif; ?>
</body>
</html>