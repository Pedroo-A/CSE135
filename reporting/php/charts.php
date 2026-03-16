<?php 
require_once('auth.php'); 

$host = 'localhost';
$db   = 'analytics_db';
$user = 'collector_bot';
$pass = 'Pedro3344752';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    http_response_code(500);
    exit("Database conn failed");
}

// last 20 entries for charts
$stmt = $pdo->query("SELECT session_id, payload, created_at FROM raw_metrics ORDER BY created_at DESC LIMIT 20");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
// New query for daily visits (Last 14 days)
$dailyStmt = $pdo->query("
    SELECT DATE(created_at) as visit_date, COUNT(*) as visit_count 
    FROM raw_metrics 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
    GROUP BY visit_date 
    ORDER BY visit_date ASC
");
$dailyRows = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

$dailyLabels = [];
$dailyCounts = [];

foreach ($dailyRows as $row) {
    $dailyLabels[] = $row['visit_date'];
    $dailyCounts[] = (int)$row['visit_count'];
}

//arrays to hold chart data
$sessionLabels = [];
$loadTimes = [];
$clickCounts = [];
$keyCounts = [];
$errorCounts = [];
$mobileCount = 0;
$desktopCount = 0;

$chartRows = array_reverse($rows);

foreach ($chartRows as $row) {
    $payload = json_decode($row['payload'], true);
    
    // Data for Performance Line Chart
    $sid = substr($row['session_id'], 0, 6);
    $sessionLabels[] = $sid;
    
    $loadTime = $payload['performance']['totalLoadTime'] ?? 0;
    $loadTimes[] = round($loadTime, 2);

    $width = $payload['static']['screenWidth'] ?? 1024;
    if ($width < 768) {
        $mobileCount++;
    } else {
        $desktopCount++;
    }
    // data for User Activity (Clicks & Keys)
    $act = $payload['activity'] ?? [];
    $clickCounts[] = count($act['clicks'] ?? []);
    $keyCounts[] = count($act['keyStrokes'] ?? []);
}
$errorStmt = $pdo->query("
    SELECT payload 
    FROM raw_metrics 
    WHERE payload LIKE '%\"errors\":[%' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$errorRows = $errorStmt->fetchAll(PDO::FETCH_ASSOC);

$errorCounts = [];
foreach ($errorRows as $erow) {
    $p = json_decode($erow['payload'], true);
    $act = $p['activity'] ?? [];
    if (!empty($act['errors'])) {
        $page = basename($act['pageUrl'] ?? 'Unknown');
        $errorCounts[$page] = ($errorCounts[$page] ?? 0) + count($act['errors']);
    }
}
$errorLabels = empty($errorCounts) ? ['No Errors'] : array_keys($errorCounts);
$errorData = empty($errorCounts) ? [1] : array_values($errorCounts);
$errorColors = empty($errorCounts) ? "['#e9ecef']" : "['#dc3545', '#fd7e14', '#ffc107', '#6f42c1', '#d63384']";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Detailed Reports</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
        <style>
            :root { 
                --navbar-width: 240px; 
            }
            * { 
                box-sizing: border-box; 
                margin: 0; 
                padding: 0; 
            }
            body { 
                font-family: sans-serif; 
                background-color: #f8f9fa; 
                display: flex; 
            }

            /*navbar */
            nav { 
                width: var(--navbar-width); 
                background: #343a40; 
                color: white; 
                height: 100vh; 
                position: fixed; 
                padding: 20px; 
            }
            nav h2 { 
                font-size: 1.2rem; 
                margin-bottom: 30px; 
                opacity: 0.8; 
            }
            nav a { 
                color: #adb5bd; 
                text-decoration: none; 
                display: block; 
                padding: 10px 0; 
                border-bottom: 1px solid #495057;
             }
            nav a:hover { 
                color: white; 
            }

            /* Main Area */
            main { 
                margin-left: var(--navbar-width); 
                padding: 40px; 
                width: 100%; 
            }
            header { 
                margin-bottom: 30px; 
            }
            header h1 { 
                font-size: 24px; 
                color: #333; 
            }

            /* Chart */
            .charts-grid { 
                display: grid;
                grid-template-columns: 2fr 1fr; 
                gap: 20px; 
                margin-bottom: 40px; 
            }
            @media (max-width: 1000px) { 
                .charts-grid { 
                    grid-template-columns: 1fr; 
                } 
            }
            
            .chart-card { 
                background: white; 
                padding: 20px; 
                border-radius: 8px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            }
            .chart-card h3 { 
                font-size: 16px; 
                color: #495057; 
                margin-bottom: 15px; 
                border-bottom: 1px solid #eee; 
                padding-bottom: 10px; 
            }
            
            /* constraint size for canvas */
            .canvas-container { 
                position: relative; 
                height: 300px; 
                width: 100%; 
            }
        </style>
    </head>
    <body>

        <nav>   
            <h2>CSE 135 Reporting</h2>
            <a href="dashboard.php">Overview</a>
            <a href="charts.php" class="active">Charts</a> 
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                <a href="manage-users.php">Manage Users</a>
            <?php endif; ?>
            <a href="logout.php" style="margin-top: 50px; color: #f43131;">Logout</a>
        </nav>

        <main>
            <header>
                <h1>Charts</h1>
            </header>
            <div class="charts-grid">
                <?php if (hasAccess('performance') || hasAccess('diagnostic')): ?>
                <div class="chart-card">
                    <h3>Page Load Time (Last 20 Sessions)</h3>
                    <div class="canvas-container">
                        <canvas id="loadTimeChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (hasAccess('performance') ||hasAccess('behavior')): ?>
                <div class="chart-card">
                    <h3>Device Distribution</h3>
                    <div class="canvas-container">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (hasAccess('behavior')): ?>
                <div class="chart-card">
                    <h3>User Interaction: Clicks vs Keystrokes</h3>
                    <div class="canvas-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (hasAccess('diagnostic')): ?>
                <div class="chart-card">
                    <h3>Error Frequency by Page</h3>
                    <div class="canvas-container">
                        <canvas id="errorChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (hasAccess('performance') ||hasAccess('behavior')): ?>
                <div class="chart-card" style="grid-column: 1 / -1;">
                    <h3>Daily Page Visits (Last 14 Days)</h3>
                    <div class="canvas-container">
                        <canvas id="dailyVisitsChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        </main>

        <script>
            const labels = <?= json_encode($sessionLabels) ?>;
            const loadData = <?= json_encode($loadTimes) ?>;
            const desktopCount = <?= $desktopCount ?>;
            const mobileCount = <?= $mobileCount ?>;

            //line chart
            const ctxLine = document.getElementById('loadTimeChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Load Time (ms)',
                        data: loadData,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        grace: '10%',
                        beginAtZero: false,
                        y: { beginAtZero: true, title: { 
                            display: true, text: 'Milliseconds'
                        }},
                        x: { title: {
                            display: true, 
                            text: 'Session ID' 
                        }}
                    }
                }
            });
    
            //Doughnought chart
            const ctxDoughnut = document.getElementById('deviceChart').getContext('2d');
            new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: ['Desktop', 'Mobile/Tablet'],
                    datasets: [{
                        data: [desktopCount, mobileCount],
                        backgroundColor: ['#28a745', '#ffc107'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // bar chart: clicks vs keys
            new Chart(document.getElementById('activityChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Clicks',
                            data: <?= json_encode($clickCounts) ?>,
                            backgroundColor: '#17a2b8'
                        },
                        {
                            label: 'Keystrokes',
                            data: <?= json_encode($keyCounts) ?>,
                            backgroundColor: '#6c757d'
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });
            new Chart(document.getElementById('errorChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($errorLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($errorData) ?>,
                        backgroundColor: <?= $errorColors ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // Daily Visits Chart
            new Chart(document.getElementById('dailyVisitsChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($dailyLabels) ?>,
                    datasets: [{
                        label: 'Visits',
                        data: <?= json_encode($dailyCounts) ?>,
                        borderColor: '#343a40',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        </script>

    </body>
</html>