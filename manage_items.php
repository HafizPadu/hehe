<?php include 'includes/header.php'; ?>
<?php include 'config.php'; ?>
<?php include 'includes/sidebar.php'; ?>

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
        color: #2c3e50;
    }

    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 25px;
        margin-bottom: 30px;
    }

    form {
        margin-bottom: 25px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .form-group {
        flex: 1;
        min-width: 200px;
    }

    form label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #495057;
    }

    form input, form select {
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        width: 100%;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    form input:focus, form select:focus {
        border-color: #007BFF;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    }

    form button {
        background-color: #007BFF;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.3s;
        height: 40px;
    }

    form button:hover {
        background-color: #0069d9;
    }

    .message {
        padding: 12px 20px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .success {
        background-color: #e6ffed;
        color: #1f7d45;
        border-left: 4px solid #28a745;
    }

    .error {
        background-color: #fff0f0;
        color: #c92a2a;
        border-left: 4px solid #dc3545;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 14px;
    }

    table th, table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }

    table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    table tr:nth-child(even) {
        background-color: #f8fafc;
    }

    table tr:hover {
        background-color: #f1f5f9;
    }

    .status-available {
        color: #28a745;
        font-weight: 500;
    }

    .status-unavailable {
        color: #dc3545;
        font-weight: 500;
    }

    .actions {
        display: flex;
        gap: 10px;
    }

    .actions a {
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .edit-btn {
        background-color: #e3f2fd;
        color: #1976d2;
        border: 1px solid #bbdefb;
    }

    .edit-btn:hover {
        background-color: #bbdefb;
    }

    .delete-btn {
        background-color: #ffebee;
        color: #d32f2f;
        border: 1px solid #ffcdd2;
    }

    .delete-btn:hover {
        background-color: #ffcdd2;
    }

    .search-container {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
    }

    .search-container input {
        flex: 1;
        max-width: 300px;
        padding: 10px 15px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }

    .search-container button {
        padding: 10px 15px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    .search-container button:hover {
        background-color: #5a6268;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 5px;
    }

    .pagination a, .pagination span {
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        text-decoration: none;
        color: #007BFF;
        border-radius: 4px;
    }

    .pagination a:hover {
        background-color: #e9ecef;
    }

    .pagination .current {
        background-color: #007BFF;
        color: white;
        border-color: #007BFF;
    }
</style>

<div class="content">
    <h2>Manage Inventory</h2>
    
    <div class="card">
        <!-- Display messages -->
        <?php
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            echo "<div class='message success'>✅ $message</div>";
        }
        
        if (isset($_GET['error'])) {
            $error = htmlspecialchars($_GET['error']);
            echo "<div class='message error'>❌ $error</div>";
        }
        ?>

        <!-- Search Form -->
        <div class="search-container">
            <form method="get" action="" style="margin: 0; width: 100%; display: flex;">
                <input type="text" name="search" placeholder="Search by ID, name or type..." 
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit">Search</button>
                <?php if (isset($_GET['search'])): ?>
                    <a href="manage_items.php" style="margin-left: 10px; align-self: center; color: #6c757d;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Add Item Form -->
        <form method="post" action="">
            <div class="form-group">
                <label for="item_id">Serial Number</label>
                <input type="text" id="item_id" name="item_id" placeholder="ITM-001" required>
            </div>
            
            <div class="form-group">
                <label for="item_type">Item Type</label>
                <select id="item_type" name="item_type" required>
                    <option value="">-- Select --</option>
                    <option value="PC">PC</option>
                    <option value="MONITOR">Monitor</option>
                    <option value="NOTEBOOK">Notebook</option>
                    <option value="PRINTER">Printer</option>
                    <option value="PHONE">Phone</option>
                    <option value="TABLET">Tablet</option>
                    <option value="OTHER">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="item_name">Model Name</label>
                <input type="text" id="item_name" name="item_name" placeholder="Dell XPS 15" required>
            </div>
            
            <button type="submit" name="add_item">Add Item</button>
        </form>

        <!-- Process Form Submission -->
        <?php
        if (isset($_POST['add_item'])) {
            $id = trim($_POST['item_id']);
            $type = trim($_POST['item_type']);
            $name = trim($_POST['item_name']);

            // Validate inputs
            if (empty($id) || empty($type) || empty($name)) {
                echo "<div class='message error'>❌ Please fill all fields!</div>";
            } else {
                $check = $conn->prepare("SELECT * FROM items WHERE serial_number = ?");
                $check->bind_param("s", $id);
                $check->execute();
                $checkResult = $check->get_result();

                if ($checkResult->num_rows > 0) {
                    echo "<div class='message error'>❌ Item with this serial number already exists!</div>";
                } else {
                    $stmt = $conn->prepare("INSERT INTO items (serial_number, item, model, status) VALUES (?, ?, ?, 'available')");
                    $stmt->bind_param("sss", $id, $type, $name);
                    
                    if ($stmt->execute()) {
                        header("Location: manage_items.php?message=Item+added+successfully");
                        exit();
                    } else {
                        echo "<div class='message error'>❌ Error adding item: " . $conn->error . "</div>";
                    }
                }
            }
        }
        ?>
    </div>

    <div class="card">
        <!-- Display Items Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Serial Number</th>
                        <th>Item Type</th>
                        <th>Model</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Pagination variables
                    $limit = 10;
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($page - 1) * $limit;
                    
                    // Search functionality
                    $search = isset($_GET['search']) ? $_GET['search'] : '';
                    $where = '';
                    $params = [];
                    $types = '';
                    
                    if (!empty($search)) {
                        $where = "WHERE serial_number LIKE ? OR item LIKE ? OR model LIKE ?";
                        $searchTerm = "%$search%";
                        $params = [$searchTerm, $searchTerm, $searchTerm];
                        $types = "sss";
                    }
                    
                    // Get total count for pagination
                    $countQuery = "SELECT COUNT(*) as total FROM items $where";
                    $countStmt = $conn->prepare($countQuery);
                    
                    if (!empty($params)) {
                        $countStmt->bind_param($types, ...$params);
                    }
                    
                    $countStmt->execute();
                    $totalResult = $countStmt->get_result();
                    $totalRows = $totalResult->fetch_assoc()['total'];
                    $totalPages = ceil($totalRows / $limit);
                    
                    // Get data
                    $query = "SELECT * FROM items $where ORDER BY item, model LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($query);
                    
                    if (!empty($params)) {
                        $params[] = $limit;
                        $params[] = $offset;
                        $types .= "ii";
                        $stmt->bind_param($types, ...$params);
                    } else {
                        $stmt->bind_param("ii", $limit, $offset);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $statusClass = $row['status'] == 'available' ? 'status-available' : 'status-unavailable';

                            echo "<tr>";
                            echo "<td>{$row['serial_number']}</td>";
                            echo "<td>{$row['item']}</td>";
                            echo "<td>{$row['model']}</td>";
                            echo "<td class='$statusClass'>{$row['status']}</td>";
                            echo "<td class='actions'>";

                            if ($_SESSION['role'] !== 'viewer') {
                                echo "<a href='edit_item.php?id={$row['serial_number']}' class='edit-btn'>✏ Edit</a>";
                            }

                            if ($_SESSION['role'] === 'admin'|| $_SESSION['role'] === 'super_admin') {
                                echo "<a href='delete_item.php?id={$row['serial_number']}' 
                                    class='delete-btn' 
                                    onclick=\"return confirm('Are you sure you want to delete this item? This action cannot be undone.');\">🗑 Delete</a>";
                            }

                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center;'>No items found</td></tr>";
                    }

                    ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">&laquo; Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page+1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>