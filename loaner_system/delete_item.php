<?php
include 'includes/header.php';
include 'config.php';
include 'includes/sidebar.php';

if (!isset($_GET['id'])) {
    echo "<script>alert('❌ No item selected to delete.'); window.location.href='manage_items.php';</script>";
    exit;
}

$id = $_GET['id'];

// Fetch item
$stmt = $conn->prepare("SELECT * FROM items WHERE serial_number = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    echo "<script>alert('❌ Item not found.'); window.location.href='manage_items.php';</script>";
    exit;
}

$status = $item['status'];

// If deletion confirmed and item is available
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $status === 'available') {
    $delete = $conn->prepare("DELETE FROM items WHERE serial_number = ?");
    $delete->bind_param("s", $id);
    if ($delete->execute()) {
        echo "<script>alert('✅ Item deleted successfully.'); window.location.href='manage_items.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to delete item.'); window.location.href='manage_items.php';</script>";
    }
    exit;
}
?>

<style>
.modal {
    display: block;
    position: fixed;
    z-index: 999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #ccc;
    width: 400px;
    text-align: center;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.modal-buttons {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

.modal-buttons button,
.modal-buttons a {
    padding: 10px 20px;
    border-radius: 6px;
    border: none;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
}

.confirm-btn { background-color: #dc3545; color: white; }
.cancel-btn { background-color: #6c757d; color: white; }
.confirm-btn:hover { background-color: #c82333; }
.cancel-btn:hover { background-color: #5a6268; }
</style>

<div class="modal">
    <div class="modal-content">
        <?php if ($status === 'loaned'): ?>
            <h3>Item In Use</h3>
            <p>This item (<strong><?= htmlspecialchars($item['serial_number']) ?> - <?= htmlspecialchars($item['model']) ?></strong>) is currently <strong>loaned out</strong> and cannot be deleted.</p>
            <div class="modal-buttons">
                <a href="manage_items.php" class="cancel-btn">Back</a>
            </div>
        <?php else: ?>
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this item?</p>
            <p><strong><?= htmlspecialchars($item['serial_number']) ?> - <?= htmlspecialchars($item['model']) ?></strong></p>
            <form method="post" class="modal-buttons">
                <button type="submit" name="confirm_delete" class="confirm-btn">Yes, Delete</button>
                <a href="manage_items.php" class="cancel-btn">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
