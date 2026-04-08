<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Realm</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }

        .sidebar-logo h2 {
            font-size: 24px;
            color: #c53940;
        }

        .sidebar-logo p {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #bbb;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: #34495e;
            color: white;
            border-left: 3px solid #2c3d4f;
        }

        .nav-icon {
            flex-shrink: 0;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            align-items: center;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);
        }

        .top-bar h1 {
            font-size: 24px;
            color: #333;
        }

        .user-info {
            color: #666;
            font-size: 14px;
        }

        .panel {
            background: white;
            border-radius: 8px;
            padding: 25px;
            padding: 25px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);
            margin-bottom: 20px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .panel-header h2 {
            font-size: 20px;
            color: #333;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 20px;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 2px 0 rgba(0,0,0,.16), 0 2px 4px rgba(0,0,0,.26);
        }

        .stat-icon-wrapper {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon {
            color: #2c3d4f;
        }

        .stat-content {
            flex: 1;
        }

        .stat-content h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .stat-number {
            font-size: 32px;
            color: #333;
            font-weight: 700;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #f8f9fa;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        table th {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        table td {
            color: #666;
            font-size: 14px;
        }

        .table-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .no-image {
            width: 50px;
            height: 50px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #999;
            border-radius: 4px;
        }

        .btn, .btn-primary, .btn-secondary, .btn-danger {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2c3d4f;
            color: white;
        }

        .btn-primary:hover {
            background: #1e2b3a;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2c3d4f;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .image-preview {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid #ddd;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #2c3d4f;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .mobile-menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
                z-index: 1000;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 80px 15px 30px;
            }

            .top-bar {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .top-bar h1 {
                font-size: 20px;
            }

            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 20px 15px;
            }

            .panel-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            table th,
            table td {
                padding: 8px 6px;
            }

            .table-image {
                width: 40px;
                height: 40px;
            }
            .table-image {
                width: 40px;
                height: 40px;
            }
        }
        
        /* General Mobile Table Optimization */
        @media (max-width: 768px) {
            .responsive-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Card view for tables on mobile */
            .mobile-card-table thead {
                display: none;
            }
            
            .mobile-card-table tbody,
            .mobile-card-table tr,
            .mobile-card-table td {
                display: block;
                width: 100%;
            }
            
            .mobile-card-table tr {
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 15px;
                background: white;
                box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);
            }
            
            .mobile-card-table td {
                padding: 8px 0;
                border: none;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
            }
            
            .mobile-card-table td:last-child {
                border-bottom: none;
            }
            
            .mobile-card-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #666;
                text-align: left;
                margin-right: 15px;
            }
        }

        @media (max-width: 768px) {
            /* Mobile-optimized messages table */
            .messages-table-wrapper table {
                display: block;
            }

            .messages-table-wrapper thead {
                display: none;
            }

            .messages-table-wrapper tbody,
            .messages-table-wrapper tr,
            .messages-table-wrapper td {
                display: block;
                width: 100%;
            }

            .messages-table-wrapper tr {
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 15px;
                background: white;
                position: relative;
            }

            .messages-table-wrapper td {
                padding: 8px 0 !important;
                border: none !important;
                text-align: left !important;
            }

            .messages-table-wrapper td:before {
                content: attr(data-label);
                font-weight: 600;
                display: inline-block;
                width: 80px;
                color: #666;
                font-size: 13px;
            }

            /* Hide unread indicator column on mobile, show as badge instead */
            .messages-table-wrapper td:first-child {
                position: absolute;
                top: 15px;
                right: 15px;
                padding: 0 !important;
            }

            .messages-table-wrapper td:first-child:before {
                display: none;
            }

            /* Stack action buttons vertically on mobile */
            .messages-table-wrapper td:last-child {
                padding-top: 15px !important;
                border-top: 1px solid #f0f0f0 !important;
                margin-top: 10px;
            }

            .messages-table-wrapper td:last-child .btn {
                display: block;
                width: 100%;
                margin-bottom: 8px;
                text-align: center;
            }

            .messages-table-wrapper td:last-child:before {
                display: none;
            }

            /* Filter tabs responsive */
            .filter-tabs-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .filter-tabs-wrapper > div {
                gap: 10px !important;
            }

            .filter-tabs-wrapper a {
                white-space: nowrap;
                padding: 10px 15px !important;
                font-size: 13px !important;
            }
        }

        @media (max-width: 480px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
            }

            .stat-info h3 {
                font-size: 24px;
            }

            .btn,
            .btn-primary,
            .btn-secondary {
                font-size: 13px;
                padding: 8px 15px;
            }
        }

        /* Product Card Styles (Global) */
        .admin-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-product-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
            display: flex;
            flex-direction: column;
        }

        .admin-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 2px 0 rgba(0,0,0,.16), 0 2px 4px rgba(0,0,0,.26);
        }

        .admin-product-image {
            position: relative;
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .admin-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image-placeholder {
            color: #999;
            font-size: 14px;
        }

        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffc107;
            color: #000;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .admin-product-info {
            padding: 15px;
            flex: 1;
        }

        .admin-product-info h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-category {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .product-price {
            font-size: 18px;
            font-weight: 600;
            color: #c53940;
            margin-bottom: 5px;
        }

        .product-date {
            font-size: 12px;
            color: #999;
        }

        .admin-product-actions {
            padding: 15px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
        }

        .admin-product-actions .btn-sm {
            flex: 1;
            text-align: center;
        }

        @media (max-width: 768px) {
            .admin-products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
        }

        /* Icon Buttons */
        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            background: #f5f5f5;
            color: #666;
        }

        .icon-btn:hover {
            opacity: 0.8;
        }

        .icon-btn-primary {
            background: #2c3d4f;
            color: white;
        }

        .icon-btn-success {
            background: #38ce3c;
            color: white;
        }

        .icon-btn-warning {
            background: #f57c00;
            color: white;
        }

        .icon-btn-danger {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <div class="admin-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" style="width: 100px; height: auto; filter: brightness(0) invert(1);">
                <p>Admin Panel</p>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/orders.php" <?php echo in_array(basename($_SERVER['PHP_SELF']), ['orders.php', 'order-view.php']) ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span>Orders</span>
                        <?php
                        try {
                            $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
                            if ($pendingOrders > 0):
                        ?>
                        <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: auto;"><?php echo $pendingOrders; ?></span>
                        <?php
                            endif;
                        } catch (PDOException $e) {
                            // Table doesn't exist yet
                        }
                        ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/categories.php" <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>Categories</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/products.php" <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'add-product.php', 'edit-product.php']) ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <span>Products</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/promotions.php" <?php echo basename($_SERVER['PHP_SELF']) == 'promotions.php' ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                        <span>Promotions</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/messages.php" <?php echo in_array(basename($_SERVER['PHP_SELF']), ['messages.php', 'view-message.php']) ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <span>Messages</span>
                        <?php
                        try {
                            $unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
                            if ($unreadCount > 0):
                        ?>
                        <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: auto;"><?php echo $unreadCount; ?></span>
                        <?php
                            endif;
                        } catch (PDOException $e) {
                            // Table doesn't exist yet, ignore
                        }
                        ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/support.php" <?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                        <span>Live Support</span>
                        <?php
                        try {
                            $fp = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender_type = 'user' AND is_read = 0");
                            $chatUnread = $fp ? $fp->fetchColumn() : 0;
                            if ($chatUnread > 0):
                        ?>
                        <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: auto;"><?php echo $chatUnread; ?></span>
                        <?php
                            endif;
                        } catch (PDOException $e) { }
                        ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/analytics.php" <?php echo in_array(basename($_SERVER['PHP_SELF']), ['analytics.php', 'activity-logs.php', 'sessions.php']) ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="20" x2="12" y2="10"></line>
                            <line x1="18" y1="20" x2="18" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="16"></line>
                        </svg>
                        <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/marketing.php" <?php echo basename($_SERVER['PHP_SELF']) == 'marketing.php' ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>Marketing</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/settings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'class="active"' : ''; ?>>
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo ADMIN_URL; ?>/logout.php">
                        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><?php echo ucfirst(str_replace(['-', '.php'], [' ', ''], basename($_SERVER['PHP_SELF']))); ?></h1>
                <div class="user-info">
                    Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                </div>
            </div>

            <?php
            // Removed getMessage() - function doesn't exist
            // $msg = getMessage();
            // if ($msg):
            ?>
            <?php /* 
            <div class="alert alert-<?php echo $msg['type']; ?>">
                <?php echo htmlspecialchars($msg['message']); ?>
            </div>
            <?php endif; */ ?>

            <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('mobile-open');
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.querySelector('.mobile-menu-toggle');

                if (window.innerWidth <= 992) {
                    if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                        sidebar.classList.remove('mobile-open');
                    }
                }
            });
            </script>



