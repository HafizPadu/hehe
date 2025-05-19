<?php
include 'config.php';

$result = $conn->query("
    SELECT DATE_FORMAT(loan_date, '%Y-%m') as month, COUNT(*) as count
    FROM loans
    GROUP BY month
    ORDER BY month ASC
");

$labels = [];
$data = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['month'];
    $data[] = $row['count'];
}

echo json_encode(['labels' => $labels, 'data' => $data]);
?>
