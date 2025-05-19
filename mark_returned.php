<?php
include 'config.php';

// Validate loan ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // You could redirect or show a user-friendly message here
    die("Invalid loan ID.");
}

$loan_id = (int)$_GET['id'];

try {
    // Begin transaction
    $conn->begin_transaction();

    // Step 1: Get serial_number from loans where status is 'loaned'
    $stmtSelect = $conn->prepare("SELECT serial_number FROM loans WHERE SN = ? AND status = 'loaned'");
    $stmtSelect->bind_param("i", $loan_id);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Loan not found or already returned.");
    }

    $row = $result->fetch_assoc();
    $serial_number = $row['serial_number'];

    // Step 2: Update loan status to 'returned'
    $stmtUpdateLoan = $conn->prepare("UPDATE loans SET status = 'returned' WHERE SN = ?");
    $stmtUpdateLoan->bind_param("i", $loan_id);
    $stmtUpdateLoan->execute();

    // Step 3: Update item status to 'available'
    $stmtUpdateItem = $conn->prepare("UPDATE items SET status = 'available' WHERE serial_number = ?");
    $stmtUpdateItem->bind_param("s", $serial_number);
    $stmtUpdateItem->execute();

    // Commit transaction
    $conn->commit();

    // Redirect to manage loans page with success message
    header("Location: manage_loans.php?returned=1");
    exit;

} catch (Exception $e) {
    // Rollback if anything goes wrong
    $conn->rollback();

    // Log the error in real apps (e.g., file or error monitoring system)
    error_log("Error returning loan: " . $e->getMessage());

    // Show a friendly message to the user
    die("Something went wrong while marking the loan as returned.");
}
?>


