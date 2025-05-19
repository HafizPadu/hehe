<?php
include 'config.php';

if (isset($_POST['save'])) {
    $siri_number = $_POST['siri_number'];
    $model = $_POST['model'];
    $location = $_POST['location'];
    $status = $_POST['status'];
    $category = $_POST['category'];
    $borrower_name = $_POST['borrower_name'];
    $loan_date = $_POST['loan_date'];

    $sql = "INSERT INTO loaners (siri_number, model, location, status, category, borrower_name, loan_date) 
            VALUES ('$siri_number', '$model', '$location', '$status', '$category', '$borrower_name', '$loan_date')";

    if ($conn->query($sql) === TRUE) {
        header('Location: index.php');
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="css/style.css">

    <meta charset="UTF-8">
    <title>Add New Loaner</title>
</head>
<body>

<h1>Add New Loaner</h1>

<form method="post">
    Serial Number: <input type="text" name="siri_number" required><br><br>
    Model: <input type="text" name="model" required><br><br>
    Location: <input type="text" name="location" required><br><br>
    Status: 
    <select name="status" required>
        <option value="Available">Available</option>
        <option value="Loaned">Loaned</option>
        <option value="Repair">Repair</option>
    </select><br><br>
    Category:
    <select name="category" required>
        <option value="PC">PC</option>
        <option value="Laptop">Laptop</option>
        <option value="Monitor">Monitor</option>
        <option value="Printer">Printer</option>
        <option value="Keyboard">Keyboard</option>
        <option value="Mouse">Mouse</option>
    </select><br><br>
    Borrower Name (if loaned): <input type="text" name="borrower_name"><br><br>
    Loan Date (if loaned): <input type="date" name="loan_date"><br><br>

    <input type="submit" name="save" value="Save Loaner">
</form>

</body>
</html>
