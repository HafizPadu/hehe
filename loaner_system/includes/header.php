<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loaner System</title>
    <link rel="stylesheet" href="https://kit.fontawesome.com/a076d05399.css" crossorigin="anonymous">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
        }

        .sidebar {
            width: 200px;
            background-color: #2c3e50;
            height: 100vh;
            padding-top: 20px;
            color: white;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 12px;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .content {
            padding: 20px;
            flex: 1;
        }
    </style>
</head>
<body>
