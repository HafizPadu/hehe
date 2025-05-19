<?php
include 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM loaners WHERE id=$id";
    $result = $conn->query($sql);
    $loaner = $result->fetch_assoc();
}

if (isset($_POST['update_status'])) {
    $status = $_POST['status'];

    $updateSql = "UPDATE loaners SET status='$status' WHERE id=$id";

    if ($conn->query($updateSql) === TRUE) {
        header('Location: index.php');
    } else {
        echo "Error updating status: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="css/style.css">

    <meta charset="UTF-8">
    <title>Update Loaner Status</title>
</head>
<body>

<h1>Update Status</h1>

<form method="post">
    Status:
    <select name="status">
        <option value="Available" <?php if($loaner['status']=='Available') echo 'selected'; ?>>Available</option>
        <option value="Loaned" <?php if($loaner['status']=='Loaned') echo 'selected'; ?>>Loaned</option>
        <option value="Repair" <?php if($loaner['status']=='Repair') echo 'selected'; ?>>Repair</option>
    </select><br><br>
    <input type="submit" name="update_status" value="Update Status">
</form>

</body>
</html>
