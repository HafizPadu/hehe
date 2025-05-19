<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'config.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Get list of distinct locations
$locationsResult = $conn->query("SELECT DISTINCT location FROM loans ORDER BY location ASC");
$locations = [];
while ($row = $locationsResult->fetch_assoc()) {
    $locations[] = $row['location'];
}

// Get selected location from GET
$selectedLocation = isset($_GET['location']) ? $_GET['location'] : '';
$locationQueryPart = '';
if (!empty($selectedLocation)) {
    $safeLocation = $conn->real_escape_string($selectedLocation);
    $locationQueryPart = "WHERE location = '$safeLocation'";
}

// Recent activity query with optional location filter
$recentLoans = $conn->query("SELECT l.serial_number, l.loaner_name, l.start_date, l.return_date, l.status, l.location 
                             FROM loans l
                             $locationQueryPart 
                             ORDER BY l.start_date DESC 
                             LIMIT 5");

// Stats
$totalItems = $conn->query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'];
$loanedItems = $conn->query("SELECT COUNT(*) as total FROM items WHERE status = 'loaned'")->fetch_assoc()['total'];
$availableItems = $conn->query("SELECT COUNT(*) as total FROM items WHERE status = 'available'")->fetch_assoc()['total'];
$activeLoaners = $conn->query("SELECT COUNT(DISTINCT loaner_name) as total FROM loans WHERE status = 'loaned'")->fetch_assoc()['total'];

// Monthly summary
$currentMonth = date('m');
$currentYear = date('Y');

$loansThisMonth = $conn->query("SELECT COUNT(*) as total FROM loans WHERE MONTH(start_date) = $currentMonth AND YEAR(start_date) = $currentYear")->fetch_assoc()['total'];
$returnsThisMonth = $conn->query("SELECT COUNT(*) as total FROM loans WHERE MONTH(return_date) = $currentMonth AND YEAR(return_date) = $currentYear")->fetch_assoc()['total'];
$mostLoanedItem = $conn->query("SELECT l.serial_number, COUNT(*) as count 
                               FROM loans l
                               GROUP BY l.serial_number 
                               ORDER BY count DESC 
                               LIMIT 1")->fetch_assoc();

// Get overdue loans
$overdueLoans = $conn->query("SELECT COUNT(*) as total FROM loans WHERE return_date < CURDATE() AND status = 'loaned'")->fetch_assoc()['total'];

// Get user-specific stats if not admin
$userSpecificStats = [];
if ($role !== 'admin' && $role !== 'super_admin') {
    $userLoans = $conn->query("SELECT COUNT(*) as total FROM loans WHERE loaner_name = '$username'")->fetch_assoc()['total'];
    $userActiveLoans = $conn->query("SELECT COUNT(*) as total FROM loans WHERE loaner_name = '$username' AND status = 'loaned'")->fetch_assoc()['total'];
    $userOverdueLoans = $conn->query("SELECT COUNT(*) as total FROM loans WHERE loaner_name = '$username' AND return_date < CURDATE() AND status = 'loaned'")->fetch_assoc()['total'];
    
    $userSpecificStats = [
        'total_loans' => $userLoans,
        'active_loans' => $userActiveLoans,
        'overdue_loans' => $userOverdueLoans
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Loaner System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e6f0ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .main-layout {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: var(--transition);
        }

        .sidebar.collapsed ~ .main-layout {
            margin-left: 70px;
        }

        .dashboard-container {
            max-width: 100%;
            margin: 0 auto;
        }

        /* Header Section */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .user-info-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--white);
            padding: 15px 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            flex-grow: 1;
            min-width: 300px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-light);
        }

        .user-details h3 {
            font-size: 20px;
            margin-bottom: 5px;
            color: var(--dark);
            font-weight: 600;
        }

        .user-details p {
            font-size: 14px;
            color: var(--gray);
        }

        .user-role {
            display: inline-block;
            padding: 4px 10px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .dashboard-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        /* Quick Actions */
        .dashboard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            font-size: 15px;
            white-space: nowrap;
        }

        .primary-btn {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }

        .primary-btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(67, 97, 238, 0.3);
        }

        .secondary-btn {
            background-color: var(--light-gray);
            color: var(--dark);
            border: 1px solid #ddd;
        }

        .secondary-btn:hover {
            background-color: #e2e6ea;
            border-color: #ccc;
        }

        .report-btn {
            background: linear-gradient(135deg, var(--success), #3aa8d5);
            color: white;
            padding: 12px 24px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(76, 201, 240, 0.3);
        }

        .report-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(76, 201, 240, 0.4);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary);
        }

        .stat-card.success::before {
            background-color: var(--success);
        }

        .stat-card.warning::before {
            background-color: var(--warning);
        }

        .stat-card.danger::before {
            background-color: var(--danger);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-icon.primary {
            background-color: var(--primary);
        }

        .stat-icon.success {
            background-color: var(--success);
        }

        .stat-icon.warning {
            background-color: var(--warning);
        }

        .stat-icon.danger {
            background-color: var(--danger);
        }

        .stat-title {
            font-size: 15px;
            color: var(--gray);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin: 5px 0;
            line-height: 1.2;
        }

        .stat-change {
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .stat-change.positive {
            color: #28a745;
        }

        .stat-change.negative {
            color: var(--danger);
        }

        /* User Stats (for non-admins) */
        .user-stats {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .user-stats h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .user-stat-item {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .user-stat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .user-stat-value {
            font-size: 28px;
            font-weight: 700;
            margin: 8px 0;
            color: var(--primary);
        }

        .user-stat-label {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }

        /* Recent Activity */
        .recent-activity {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .filter-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: 15px;
            color: var(--gray);
            font-weight: 500;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            font-size: 15px;
            background-color: var(--white);
            cursor: pointer;
            min-width: 200px;
            transition: var(--transition);
        }

        .filter-select:hover {
            border-color: var(--primary);
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .activity-table th {
            background-color: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--gray);
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eee;
        }

        .activity-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-size: 15px;
            vertical-align: middle;
        }

        .activity-table tr:last-child td {
            border-bottom: none;
        }

        .activity-table tr:hover td {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            min-width: 90px;
            text-align: center;
        }

        .status-loaned {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-returned {
            background-color: #d4edda;
            color: #155724;
        }

        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Responsive Adjustments */
        @media (max-width: 1200px) {
            .main-layout {
                margin-left: 70px;
            }
            
            .sidebar:not(.collapsed) ~ .main-layout {
                margin-left: 250px;
            }
        }

        @media (max-width: 992px) {
            .dashboard-container {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .user-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-layout {
                margin-left: 0;
                padding-bottom: 80px;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dashboard-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .quick-actions {
                width: 100%;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            
            .stats-grid, 
            .user-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-controls {
                width: 100%;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .activity-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>

<div class="main-layout">
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-header fade-in">
            <div class="user-info-card">
                <img src="images (16).jpeg" alt="User Avatar" class="user-avatar">
                <div class="user-details">
                    <h3><?= htmlspecialchars($username) ?></h3>
                    <p><span class="user-role"><?= htmlspecialchars(ucfirst($role)) ?></span></p>
                </div>
            </div>
            
            <h1 class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i> Dashboard Overview
            </h1>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-actions fade-in">
            <a href="generate_report.php" target="_blank" class="action-btn report-btn">
                <i class="fas fa-file-pdf"></i> Generate Report
            </a>
            
            <div class="quick-actions">
                <a href="manage_items.php?action=add" class="action-btn primary-btn">
                    <i class="fas fa-plus"></i> Add Item
                </a>
                <a href="manage_items.php" class="action-btn secondary-btn">
                    <i class="fas fa-boxes"></i> Manage Items
                </a>
                <a href="manage_loans.php" class="action-btn secondary-btn">
                    <i class="fas fa-exchange-alt"></i> Manage Loans
                </a>
            </div>
        </div>

        <!-- User Stats (for non-admins) -->
        <?php if (!empty($userSpecificStats)): ?>
        <div class="user-stats fade-in">
            <h3><i class="fas fa-user-circle"></i> Your Loan Statistics</h3>
            <div class="user-stats-grid">
                <div class="user-stat-item">
                    <div class="user-stat-value"><?= $userSpecificStats['total_loans'] ?></div>
                    <div class="user-stat-label">Total Loans</div>
                </div>
                <div class="user-stat-item">
                    <div class="user-stat-value"><?= $userSpecificStats['active_loans'] ?></div>
                    <div class="user-stat-label">Active Loans</div>
                </div>
                <div class="user-stat-item">
                    <div class="user-stat-value"><?= $userSpecificStats['overdue_loans'] ?></div>
                    <div class="user-stat-label">Overdue Loans</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Items</div>
                        <div class="stat-value"><?= $totalItems ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> 5% from last month
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Available Items</div>
                        <div class="stat-value"><?= $availableItems ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> 8% from last week
                        </div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Loaned Items</div>
                        <div class="stat-value"><?= $loanedItems ?></div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-down"></i> 3% from last week
                        </div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Overdue Loans</div>
                        <div class="stat-value"><?= $overdueLoans ?></div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-up"></i> 2 from yesterday
                        </div>
                    </div>
                    <div class="stat-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Summary -->
        <div class="stats-grid fade-in" style="margin-bottom: 30px;">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Loans This Month</div>
                        <div class="stat-value"><?= $loansThisMonth ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> 12% from last month
                        </div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Returns This Month</div>
                        <div class="stat-value"><?= $returnsThisMonth ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> 8% from last month
                        </div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-undo"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Active Loaners</div>
                        <div class="stat-value"><?= $activeLoaners ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> 5% from last week
                        </div>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Top Loaned Item</div>
                        <div class="stat-value" style="font-size: 22px; line-height: 1.3;"><?= htmlspecialchars($mostLoanedItem['item_name'] ?? $mostLoanedItem['serial_number'] ?? 'N/A') ?></div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i> Most popular
                        </div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity fade-in">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-clock"></i> Recent Activity
                </h3>
                
                <div class="filter-controls">
                    <span class="filter-label">Filter by Location:</span>
                    <select class="filter-select" onchange="window.location.href='?location='+this.value">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location) ?>" <?= $selectedLocation === $location ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Item Serial</th>
                        <th>Loaned To</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recentLoans->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['serial_number']) ?></td>
                            <td><?= htmlspecialchars($row['loaner_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($row['start_date'])) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Add active class to current nav item
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation delays
        const fadeElements = document.querySelectorAll('.fade-in');
        fadeElements.forEach((el, index) => {
            el.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Highlight active sidebar item
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.sidebar a');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    });
</script>

</body>
</html>


