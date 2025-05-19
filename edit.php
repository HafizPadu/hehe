<?php
include 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM loaners WHERE id=$id";
    $result = $conn->query($sql);
    $loaner = $result->fetch_assoc();
}

if (isset($_POST['update'])) {
    $siri_number = $_POST['siri_number'];
    $model = $_POST['model'];
    $location = $_POST['location'];
    $borrower_name = $_POST['borrower_name'];

    $updateSql = "UPDATE loaners SET siri_number='$siri_number', model='$model', location='$location', borrower_name='$borrower_name' WHERE id=$id";

    if ($conn->query($updateSql) === TRUE) {
        header('Location: index.php');
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Loaner</title>
</head>
<body>

<h1>Edit Loaner Information</h1>

<form method="post">
    Serial Number: <input type="text" name="siri_number" value="<?php echo $loaner['siri_number']; ?>"><br><br>
    Model: <input type="text" name="model" value="<?php echo $loaner['model']; ?>"><br><br>
    Location: <input type="text" name="location" value="<?php echo $loaner['location']; ?>"><br><br>
    Borrower Name: <input type="text" name="borrower_name" value="<?php echo $loaner['borrower_name']; ?>"><br><br>
    <input type="submit" name="update" value="Update Loaner">
</form>

</body>
</html>
