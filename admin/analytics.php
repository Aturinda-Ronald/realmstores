<?php
define('NO_TRACKING', true); // Don't track admin pages
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get date range from query params
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$startDate = date('Y-m-d', strtotime("-{$days} days"));

// Get analytics data
// Total visitors
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) as total FROM user_sessions WHERE first_seen >= ?");
$stmt->execute([$startDate]);
$totalVisitors = $stmt->fetchColumn();

// Total page views
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_activities WHERE action_type = 'page_view' AND created_at >= ?");
$stmt->execute([$startDate]);
$totalPageViews = $stmt->fetchColumn();

// Active users (last 5 minutes)
$stmt = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM user_sessions WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$activeUsers = $stmt->fetchColumn();

// Total actions  
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activities WHERE created_at >= ?");
$stmt->execute([$startDate]);
$totalActions = $stmt->fetchColumn();

// Visitors over time (for chart)
$stmt = $pdo->prepare("
    SELECT DATE(first_seen) as date, COUNT(DISTINCT session_id) as count
    FROM user_sessions
    WHERE first_seen >= ?
    GROUP BY DATE(first_seen)
    ORDER BY date ASC
");
$stmt->execute([$startDate]);
$visitorsOverTime = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top pages
$stmt = $pdo->prepare("
    SELECT page_url, COUNT(*) as views
    FROM user_activities  
    WHERE action_type = 'page_view' AND created_at >= ?
    GROUP BY page_url
    ORDER BY views DESC
    LIMIT 10
");
$stmt->execute([$startDate]);
$topPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Geographic distribution
$stmt = $pdo->prepare("
    SELECT country, COUNT(DISTINCT session_id) as visitors
    FROM user_sessions
    WHERE first_seen >= ?
    GROUP BY country
    ORDER BY visitors DESC
    LIMIT 10
");
$stmt->execute([$startDate]);
$geoData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Device breakdown
$stmt = $pdo->prepare("
    SELECT device_type, COUNT(DISTINCT session_id) as visitors
    FROM user_sessions
    WHERE first_seen >= ?
    GROUP BY device_type
");
$stmt->execute([$startDate]);
$deviceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Browser stats
$stmt = $pdo->prepare("
    SELECT browser, COUNT(DISTINCT session_id) as visitors
    FROM user_sessions
    WHERE first_seen >= ?
    GROUP BY browser
    ORDER BY visitors DESC
    LIMIT 7
");
$stmt->execute([$startDate]);
$browserData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activities
$stmt = $pdo->prepare("
    SELECT *
    FROM user_activities
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute();
$recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Analytics Overview';
include 'includes/header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="analytics-dashboard">
    <div class="page-header">
        <h1>Analytics Overview</h1>
        <div class="date-filter">
            <select onchange="window.location.href='?days='+this.value" class="form-select">
                <option value="1" <?php echo $days == 1 ? 'selected' : ''; ?>>Last 24 Hours</option>
                <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>Last 90 Days</option>
            </select>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p>Total Visitors</p>
                <h3><?php echo number_format($totalVisitors); ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <p>Page Views</p>
                <h3><?php echo number_format($totalPageViews); ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <p>Active Now</p>
                <h3><?php echo number_format($activeUsers); ?></h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <p>Total Actions</p>
                <h3><?php echo number_format($totalActions); ?></h3>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
        <div class="chart-card large">
            <h3>Visitors Over Time</h3>
            <canvas id="visitorsChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3>Device Breakdown</h3>
            <canvas id="deviceChart"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row">
        <div class="chart-card">
            <h3>Top Pages</h3>
            <canvas id="pagesChart"></canvas>
        </div>

        <div class="chart-card">
            <h3>Browser Statistics</h3>
            <canvas id="browserChart"></canvas>
        </div>
    </div>

    <!-- Geographic Distribution -->
    <div class="chart-card full-width">
        <h3>Geographic Distribution</h3>
        <div class="geo-table">
            <table class="simple-table">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Visitors</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($geoData as $geo): 
                        $percentage = $totalVisitors > 0 ? ($geo['visitors'] / $totalVisitors * 100) : 0;
                    ?>
                    <tr>
                        <td><?php echo escape($geo['country']); ?></td>
                        <td><?php echo number_format($geo['visitors']); ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                <span><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity Feed -->
    <div class="chart-card full-width">
        <h3>Recent Activity</h3>
        <div class="activity-feed">
            <?php foreach ($recentActivities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <?php if ($activity['action_type'] === 'page_view'): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    <?php elseif ($activity['action_type'] === 'add_to_cart'): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="activity-details">
                    <strong><?php echo ucfirst(str_replace('_', ' ', $activity['action_type'])); ?></strong>
                    <span class="activity-meta">
                        <?php echo $activity['city']; ?>, <?php echo $activity['country']; ?> •
                        <?php echo $activity['device_type']; ?> •
                        <?php echo escape(substr($activity['page_url'], 0, 50)); ?>
                    </span>
                </div>
                <div class="activity-time">
                    <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="activity-logs.php" class="view-all-link">View All Activity Logs →</a>
    </div>
</div>

<script>
// Prepare data for charts
const visitorsData = <?php echo json_encode($visitorsOverTime); ?>;
const deviceData = <?php echo json_encode($deviceData); ?>;
const topPagesData = <?php echo json_encode($topPages); ?>;
const browserData = <?php echo json_encode($browserData); ?>;

// Professional color palette - grey/blue tones
const primaryColor = '#2c3d4f';
const greyPalette = ['#6c757d', '#95a5a6', '#7f8c8d', '#adb5bd'];

// Visitors Over Time Chart
new Chart(document.getElementById('visitorsChart'), {
    type: 'line',
    data: {
        labels: visitorsData.map(d => d.date),
        datasets: [{
            label: 'Visitors',
            data: visitorsData.map(d => d.count),
            borderColor: primaryColor,
            backgroundColor: 'rgba(102, 126, 234, 0.05)',
            fill: true,
            tension: 0.4,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { 
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Device Breakdown Chart
new Chart(document.getElementById('deviceChart'), {
    type: 'bar',
    data: {
        labels: deviceData.map(d => d.device_type),
        datasets: [{
            label: 'Visitors',
            data: deviceData.map(d => d.visitors),
            backgroundColor: greyPalette
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Top Pages Chart
new Chart(document.getElementById('pagesChart'), {
    type: 'bar',
    data: {
        labels: topPagesData.map(d => d.page_url.substring(0, 30)),
        datasets: [{
            label: 'Page Views',
            data: topPagesData.map(d => d.views),
            backgroundColor: '#95a5a6'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});

// Browser Chart
new Chart(document.getElementById('browserChart'), {
    type: 'bar',
    data: {
        labels: browserData.map(d => d.browser),
        datasets: [{
            label: 'Visitors',
            data: browserData.map(d => d.visitors),
            backgroundColor: '#6c757d'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<style>
.analytics-dashboard {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0;
    font-size: 24px;
    color: #333;
    font-weight: 600;
}

.date-filter .form-select {
    padding: 8px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #666;
}

.stat-info {
    flex: 1;
}

.stat-info p {
    margin: 0 0 5px 0;
    color: #666;
    font-size: 13px;
}

.stat-info h3 {
    font-size: 28px;
    margin: 0;
    color: #333;
    font-weight: 700;
}

.charts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.chart-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
}

.chart-card.large {
    grid-column: span 2;
}

.chart-card.full-width {
    margin-bottom: 20px;
}

.chart-card h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.chart-card canvas {
    height: 300px !important;
}

.chart-card.large canvas {
    height: 350px !important;
}

.geo-table {
    overflow-x: auto;
}

.simple-table {
    width: 100%;
    border-collapse: collapse;
}

.simple-table th,
.simple-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.simple-table th {
    font-weight: 600;
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
}

.progress-bar {
    position: relative;
    background: #f0f0f0;
    height: 24px;
    border-radius: 12px;
    overflow: hidden;
    min-width: 100px;
}

.progress-fill {
    background: #2c3d4f;
    height: 100%;
}

.progress-bar span {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 600;
    color: #333;
}

.activity-feed {
    max-height: 500px;
    overflow-y: auto;
}

.activity-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.activity-icon {
    width: 32px;
    height: 32px;
    background: #f5f5f5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #666;
}

.activity-details {
    flex: 1;
}

.activity-details strong {
    display: block;
    color: #333;
    margin-bottom: 4px;
}

.activity-meta {
    font-size: 13px;
    color: #999;
}

.activity-time {
    font-size: 12px;
    color: #999;
    white-space: nowrap;
}

.view-all-link {
    display: inline-block;
    margin-top: 15px;
    color: #2c3d4f;
    text-decoration: none;
    font-weight: 600;
}

.view-all-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .analytics-container {
        padding: 10px;
        overflow-x: hidden;
    }
    
    .analytics-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .analytics-header h1 {
        font-size: 20px;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .summary-card {
        min-width: 0;
    }
    
    .charts-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .chart-card.large {
        grid-column: span 1;
    }
    
    .chart-card {
        padding: 15px;
        min-width: 0;
        overflow: hidden;
    }
    
    .card-title {
        font-size: 14px;
        word-wrap: break-word;
    }
    
    /* Make charts responsive */
    canvas {
        max-width: 100% !important;
        height: auto !important;
    }
    
    /* Make table scrollable on mobile */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .geo-table {
        font-size: 12px;
        min-width: 300px;
    }
    
    .geo-table th,
    .geo-table td {
        padding: 8px 6px;
        white-space: nowrap;
    }
    
    /* Hide percentage column on small screens */
    .geo-table th:nth-child(3),
    .geo-table td:nth-child(3) {
        display: none;
    }
    
    .activity-feed {
        max-height: 400px;
    }
    
    .activity-item {
        padding: 12px;
        flex-direction: column;
        gap: 8px;
    }
    
    .activity-details strong {
        font-size: 13px;
        word-break: break-word;
    }
    
    .activity-meta {
        font-size: 12px;
        word-break: break-word;
    }
    
    .activity-time {
        margin-top: 5px;
        font-size: 11px;
    }
    
    /* Ensure all text wraps properly */
    * {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
}

/* Extra small devices */
@media (max-width: 480px) {
    .analytics-container {
        padding: 5px;
    }
    
    .summary-card {
        padding: 15px;
    }
    
    .stat-value {
        font-size: 20px;
    }
    
    .stat-label {
        font-size: 11px;
    }
    
    .chart-card {
        padding: 12px;
    }
    
    .card-title {
        font-size: 13px;
    }
    
    .geo-table {
        font-size: 11px;
    }
    
    .geo-table th,
    .geo-table td {
        padding: 6px 4px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>



