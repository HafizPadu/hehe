<?php
require('fpdf/fpdf.php');
include 'config.php';

class PDF extends FPDF {
    function Header() {

        // Header
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'E-CONTENT (M) SDN BHD', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, 'SUITE NO 3A-01, BLOCK 4805 CBD PERDANA 2', 0, 1, 'C');
        $this->Cell(0, 5, 'JALAN PERDANA CYBER 12, 63000 CYBERJAYA, SELANGOR.', 0, 1, 'C');
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Dashboard Overview Report', 1, 1, 'C');
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function SummaryStats($totalItems, $loanedItems, $availableItems, $activeLoaners) {
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Total Items: ' . $totalItems, 0, 1);
        $this->Cell(0, 10, 'Currently Loaned: ' . $loanedItems, 0, 1);
        $this->Cell(0, 10, 'Available Items: ' . $availableItems, 0, 1);
        $this->Cell(0, 10, 'Active Loaners: ' . $activeLoaners, 0, 1);
        $this->Ln(10);
    }

    function ChartSummary($labels, $data) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Most Frequently Loaned Items (By Serial Number)', 0, 1);
        // List the top items by Serial Number and their loan counts
        $this->SetFont('Arial', '', 10);
        foreach ($labels as $index => $label) {
            $this->Cell(0, 10, 'Serial Number: ' . $label . ' - ' . $data[$index] . ' Loans', 0, 1);
        }
        $this->Ln(10);
    }

    function MonthlyTrends($months, $loans) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Monthly Loan Trends', 0, 1);
        $this->SetFont('Arial', '', 10);
        foreach ($months as $index => $month) {
            $this->Cell(0, 10, $month . ' - ' . $loans[$index] . ' Loans', 0, 1);
        }
        $this->Ln(10);
    }
}

// Create PDF
$pdf = new PDF();
$pdf->AddPage();

// Fetch summary statistics from the database
$totalItems = $conn->query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'];
$loanedItems = $conn->query("SELECT COUNT(*) as total FROM items WHERE status = 'loaned'")->fetch_assoc()['total'];
$availableItems = $conn->query("SELECT COUNT(*) as total FROM items WHERE status = 'available'")->fetch_assoc()['total'];
$activeLoaners = $conn->query("SELECT COUNT(DISTINCT loaner_name) as total FROM loans WHERE status = 'loaned'")->fetch_assoc()['total'];

// Fetch chart data (example for most frequently loaned by Serial Number)
$mostLoanedQuery = $conn->query("SELECT serial_number, COUNT(*) as loan_count FROM loans GROUP BY serial_number ORDER BY loan_count DESC LIMIT 5");
$mostLoanedLabels = [];
$mostLoanedData = [];
while ($row = $mostLoanedQuery->fetch_assoc()) {
    $mostLoanedLabels[] = $row['serial_number'];  // Changed to serial_number
    $mostLoanedData[] = $row['loan_count'];
}

// Fetch monthly trends
$monthlyTrendsQuery = $conn->query("SELECT MONTHNAME(loan_date) AS month, COUNT(*) AS loan_count FROM loans GROUP BY MONTH(loan_date) ORDER BY MONTH(loan_date)");
$monthlyLabels = [];
$monthlyData = [];
while ($row = $monthlyTrendsQuery->fetch_assoc()) {
    $monthlyLabels[] = $row['month'];
    $monthlyData[] = $row['loan_count'];
}

// Add Summary Stats and Data
$pdf->SummaryStats($totalItems, $loanedItems, $availableItems, $activeLoaners);
$pdf->ChartSummary($mostLoanedLabels, $mostLoanedData);
$pdf->MonthlyTrends($monthlyLabels, $monthlyData);

// Output the PDF
$pdf->Output();
?>

