<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

// Fetch all loaner records
$result = $conn->query("SELECT * FROM loaners");
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="css/style.css">

    <title>Loaner List</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        a.button {
            background-color: #3498db;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<h2>Loaner Items</h2>
<a href="add_loaner.php" class="button">Add New Loaner</a>
<a href="logout.php" class="button" style="background-color: #e74c3c;">Logout</a>
<br><br>

<table>
    <tr>
        <th>ID</th>
        <th>Serial Number</th>
        <th>Model</th>
        <th>Location</th>
        <th>Date Loaned</th>
        <th>Status</th>
        <th>Specs</th>
        <th>Actions</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['serial_number'] ?></td>
        <td><?= $row['model'] ?></td>
        <td><?= $row['location'] ?></td>
        <td><?= $row['date_loaned'] ?></td>
        <td><?= $row['status'] ?></td>
        <td><?= nl2br($row['specs']) ?></td>
        <td>
            <a class="button" href="edit_loaner.php?id=<?= $row['id'] ?>">Edit</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
