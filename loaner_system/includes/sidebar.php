<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loaner System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: linear-gradient(135deg, #1c1c2e, #2a2a3f);
            --sidebar-hover: #34344a;
            --sidebar-active: #007BFF;
            --sidebar-text: #f8f8f8;
            --sidebar-submenu: #2d2d44;
            --sidebar-submenu-hover: #3d3d5c;
            --transition: all 0.3s ease;
        }

        body {
            margin: 0;
            display: flex;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            transition: var(--transition);
            overflow-x: hidden;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toggle-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            cursor: pointer;
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
            transition: var(--transition);
            background: rgba(0, 0, 0, 0.2);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-btn:hover {
            color: white;
            background: rgba(0, 0, 0, 0.3);
        }

        .logo-container {
            margin-top: 15px;
            transition: var(--transition);
        }

        .sidebar.collapsed .logo-container {
            margin-top: 5px;
        }

        .sidebar img {
            display: block;
            margin: 0 auto;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition);
        }

        .sidebar.collapsed img {
            width: 40px;
            height: 40px;
        }

        .sidebar h3 {
            font-size: 16px;
            margin: 10px 0 0;
            font-weight: 600;
            color: var(--sidebar-text);
            transition: var(--transition);
            opacity: 1;
        }

        .sidebar.collapsed h3 {
            opacity: 0;
            height: 0;
            margin: 0;
        }

        .nav-menu {
            flex: 1;
            overflow-y: auto;
            padding: 15px 0;
        }

        .sidebar a,
        .dropdown-toggle {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
            margin: 5px 10px;
            border-radius: 6px;
            position: relative;
            overflow: hidden;
        }

        .sidebar a:hover,
        .dropdown-toggle:hover {
            background-color: var(--sidebar-hover);
            color: white;
        }

        .sidebar a.active {
            background-color: var(--sidebar-hover);
            color: white;
            border-left: 4px solid var(--sidebar-active);
        }

        .sidebar i {
            min-width: 24px;
            text-align: center;
            font-size: 16px;
            transition: var(--transition);
        }

        .nav-text {
            margin-left: 10px;
            white-space: nowrap;
            transition: var(--transition);
        }

        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            margin-left: 0;
        }

        .submenu {
            display: none;
            flex-direction: column;
            background-color: var(--sidebar-submenu);
            border-radius: 6px;
            margin: 5px 10px;
            overflow: hidden;
            animation: slideDown 0.3s ease-out;
        }

        .submenu.show {
            display: flex;
        }

        .submenu a {
            padding: 10px 15px 10px 40px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
            border-radius: 0;
        }

        .submenu a:hover {
            background-color: var(--sidebar-submenu-hover);
            color: white;
        }

        .sidebar.collapsed .submenu a {
            padding-left: 15px;
        }

        .arrow {
            margin-left: auto;
            transition: var(--transition);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }

        .arrow.rotated {
            transform: rotate(90deg);
            color: white;
        }

        .content {
            margin-left: 250px;
            padding: 30px;
            flex: 1;
            transition: var(--transition);
        }

        .sidebar.collapsed ~ .content {
            margin-left: 70px;
        }

        /* Tooltip for collapsed state */
        .sidebar.collapsed a:hover::after,
        .sidebar.collapsed .dropdown-toggle:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 70px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--sidebar-hover);
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
            z-index: 100;
            pointer-events: none;
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-50%) translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(-50%) translateX(0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar:not(.collapsed) {
                width: 250px;
                z-index: 2000;
            }
            
            .content {
                margin-left: 70px;
            }
            
            .sidebar:not(.collapsed) ~ .content {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </div>
        <div class="logo-container">
            <img src="https://tse3.mm.bing.net/th?id=OIP.p5YCTDszUXQbeICFnF3U0wHaHa&pid=Api&P=0&h=180" alt="Logo">
            <h3>Loaner System</h3>
        </div>
    </div>

    <div class="nav-menu">
        <a href="dashboard.php" data-tooltip="Dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span class="nav-text">Dashboard</span>
        </a>
        
        <a href="manage_items.php" data-tooltip="Assets Management">
            <i class="fas fa-boxes"></i>
            <span class="nav-text">Assets Management</span>
        </a>

        <div class="dropdown-toggle" onclick="toggleSubMenu(this)" data-tooltip="Manage Loans">
            <i class="fas fa-exchange-alt"></i>
            <span class="nav-text">Manage Loans</span>
            <span class="arrow">▶</span>
        </div>
        <div class="submenu">
            <a href="manage_loans.php">Manage Loans</a>
            <a href="manage_loaner.php">Manage Loaner</a>
        </div>

        <?php if ($_SESSION['role'] !== 'viewer'): ?>
            <a href="admin_users.php" data-tooltip="Admin User">
                <i class="fas fa-users-cog"></i>
                <span class="nav-text">Admin User</span>
            </a>
        <?php endif; ?>

        <a href="logout.php" data-tooltip="Logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-text">Logout</span>
        </a>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    function toggleSubMenu(element) {
        const submenu = element.nextElementSibling;
        const arrow = element.querySelector('.arrow');
        submenu.classList.toggle('show');
        arrow.classList.toggle('rotated');
    }

    // Initialize sidebar state from localStorage
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
        
        // Highlight active menu item
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
