<?php
header('Content-Type: application/json');
include 'config.php';

// Fetch top 4 most frequently loaned item types
$query = "
    SELECT type, COUNT(SN) as total
    FROM loans
    GROUP BY type
    ORDER BY total DESC
    LIMIT 4
";

$result = $conn->query($query);

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['type'];
    $data[] = (int)$row['total'];
}

// Return data as JSON
echo json_encode([
    'labels' => $labels,
    'data' => $data
]);
?>
