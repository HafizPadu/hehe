<?php
include 'includes/header.php';
include 'config.php';
include 'includes/sidebar.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Loan ID missing.");
}

// Get loan details
$stmt = $conn->prepare("SELECT * FROM loans WHERE SN = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$loan = $result->fetch_assoc();

if (!$loan) {
    die("Loan not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';

    // Optional: add logic to update return date if marked as returned
    if ($status === 'returned') {
        $stmt = $conn->prepare("UPDATE loans SET status = 'returned' WHERE SN = ?");
    } else {
        $stmt = $conn->prepare("UPDATE loans SET status = ? WHERE SN = ?");
        $stmt->bind_param("si", $status, $id);
    }

    if ($status === 'returned') {
        $stmt->bind_param("i", $id);
    }

    $stmt->execute();

    // Also update item status if returned
    if ($status === 'returned') {
        $stmt = $conn->prepare("UPDATE items SET status = 'available' WHERE serial_number = ?");
        $stmt->bind_param("s", $loan['serial_number']);
        $stmt->execute();
    }

    header("Location: manage_loans.php?updated=1");
    exit;
}
?>

<div class="content">
    <a href="manage_loans.php" style="display:inline-block; margin-bottom:20px; text-decoration:none; background:#6c757d; color:white; padding:10px 20px; border-radius:6px;">⬅️ Back to Manage Loans</a>
    <h2>Edit Loan</h2>

    <form method="post" class="loan-form">
        <label>Serial Number:</label>
        <input type="text" value="<?php echo htmlspecialchars($loan['serial_number']); ?>" disabled>

        <label>Loaner:</label>
        <input type="text" value="<?php echo htmlspecialchars($loan['loaner_name']); ?>" disabled>

        <label>Status:</label>
        <select name="status" required>
            <option value="loaned" <?php echo $loan['status'] === 'loaned' ? 'selected' : ''; ?>>Loaned</option>
            <option value="returned" <?php echo $loan['status'] === 'returned' ? 'selected' : ''; ?>>Returned</option>
        </select>

        <button type="submit">Update</button>
    </form>
</div>
