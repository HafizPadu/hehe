<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_personnel'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $profile_picture = $_FILES['profile_picture'] ?? null;

    if ($full_name && $email && $department) {
        $picture_filename = null;

        if ($profile_picture && $profile_picture['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $extension = pathinfo($profile_picture['name'], PATHINFO_EXTENSION);
            $picture_filename = uniqid('profile_', true) . '.' . $extension;
            $target_path = $target_dir . $picture_filename;

            if (!move_uploaded_file($profile_picture['tmp_name'], $target_path)) {
                die("Failed to upload profile picture.");
            }
        }

        $stmt = $conn->prepare("INSERT INTO personnel (full_name, email, department, profile_picture) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $department, $picture_filename);

        if ($stmt->execute()) {
            header("Location: manage_loaners.php?msg=Personnel+added+successfully");
            exit;
        } else {
            die("Error adding personnel: " . $stmt->error);
        }
    } else {
        die("Please fill in all fields.");
    }
} else {
    die("Invalid request.");
}
?>
