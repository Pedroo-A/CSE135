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
    exit(json_encode(["error" => "Database conn failed"]));
}

$total_entries = $pdo->query("SELECT COUNT(*) FROM raw_metrics")->fetchColumn();
$unique_sessions = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM raw_metrics")->fetchColumn();
$avg_load = $pdo->query("
    SELECT AVG(JSON_EXTRACT(payload, '$.performance.totalLoadTime')) FROM raw_metrics 
    WHERE JSON_EXTRACT(payload, '$.performance.totalLoadTime') > 0
    ")->fetchColumn();
//recent activity stuffs
$stmt = $pdo->query("
    SELECT *, 
    (SELECT TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) 
     FROM raw_metrics m2 WHERE m2.session_id = m1.session_id) as total_site_duration
    FROM raw_metrics m1
    WHERE id IN (
        SELECT MAX(id) 
        FROM raw_metrics 
        GROUP BY session_id
    ) 
    ORDER BY created_at DESC 
    LIMIT 15
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

//for search
$search_date = $_GET['search_date'] ?? '';
$search_results = [];
if (!empty($search_date)) {
    $stmt_search = $pdo->prepare("
        SELECT * FROM raw_metrics 
        WHERE DATE(created_at) = :s_date 
        AND id IN (
            SELECT MAX(id) FROM raw_metrics GROUP BY session_id
        )
        ORDER BY created_at DESC
    ");
    $stmt_search->execute(['s_date' => $search_date]);
    $search_results = $stmt_search->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Analytics Overview</title>
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

            /* navbar */
            nav { 
                width: var(--navbar-width);
                background: #343a40; 
                color: white; height: 100vh; 
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

            /* metrics */
            .stats-grid { 
                display: grid; 
                grid-template-columns: 
                repeat(auto-fit, minmax(200px, 1fr)); 
                gap: 20px; margin-bottom: 40px; 
            }
            .card { 
                background: white; 
                padding: 20px; 
                border-radius: 8px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            }
            .card h3 { 
                font-size: 14px; 
                color: #6c757d; 
                margin-bottom: 10px; 
            }
            .card .var { 
                font-size: 28px; 
                font-weight: bold; 
                color: #007bff; 
            }

            /* Tables */
            .info-table { 
                background: white; 
                padding: 20px; 
                border-radius: 8px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
                margin-bottom: 30px; 
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
            }
            th { 
                text-align: left; 
                padding: 12px; 
                border-bottom: 2px solid #dee2e6; 
                color: #495057; 
            }
            td { 
                padding: 12px; 
                border-bottom: 1px solid #dee2e6; 
                font-size: 14px; 
            }

            /* Search */
            .search-box { 
                display: flex; 
                align-items: center; 
                gap: 10px; 
                margin-bottom: 20px; 
            }
            .search-box input[type="date"] { 
                padding: 8px; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
            }
            .btn-search {
                padding: 8px 16px; 
                background: #007bff; 
                color: white; 
                border: none; 
                border-radius: 4px; 
                cursor: pointer; 
            }
            .btn-search:hover { 
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <nav>   
            <h2>CSE 135 Reporting</h2>
            <a href="dashboard.php">Overview</a>
            <a href="charts.php">Charts</a>
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                <a href="manage-users.php">Manage Users</a>
            <?php endif; ?>
            <a href="logout.php" style="margin-top: 50px; color: #f43131;">Logout</a>
        </nav>

        <main>
            <h1>Dashboard</h1>

            <div class="stats-grid">

                <div class="card">
                    <h3>Total Activity</h3>
                    <div class="var"><?= $total_entries ?></div>
                </div>
                <div class="card">
                    <h3>Unique Visitors</h3>
                    <div class="var"><?= $unique_sessions ?></div>
                </div>
                
                <?php if (hasAccess('performance')): ?>
                <div class="card">
                    <h3>Average Load Time</h3>
                    <div class="var"><?= round($avg_load, 2) ?>ms</div> 
                </div>
                <?php endif; ?>
            </div>

            <div class="info-table">
                <h2 style="margin-bottom: 20px; font-size: 18px;">Recent Activity (Top 10)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Device Type</th>
                            <th>Screen Size</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): 
                            $payload = json_decode($row['payload'], true);
                        ?>
                        <tr>
                            <td><code><?= substr($row['session_id'], 0, 8) ?>...</code></td>
                            <td><?= htmlspecialchars($payload['static']['userAgent'] ?? 'Unknown') ?></td>
                            <td><?= $payload['static']['screenWidth'] ?? '?' ?> x <?= $payload['static']['screenHeight'] ?? '?' ?></td>
                            <td><?= $row['created_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (hasAccess('diagnostic')): ?>
                <div class="info-table">
                    <h2 style="margin-bottom: 20px; font-size: 18px; color: #d9534f;">Error Log</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Page URL</th>
                                <th>Error Message</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $error_count = 0;
                            foreach ($rows as $row): 
                                $payload = json_decode($row['payload'], true);
                                if (!empty($payload['activity']['errors'])): 
                                    foreach ($payload['activity']['errors'] as $error):
                                        $error_count++;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($payload['activity']['pageUrl'] ?? 'Unknown') ?></td>
                                <td style="color: #c9302c; font-family: monospace;"><?= htmlspecialchars($error['message'] ?? 'Error') ?></td>
                                <td><?= date("H:i:s", ($error['timestamp'] / 1000)) ?></td>
                            </tr>
                            <?php endforeach; endif; endforeach; ?>
                            <?php if ($error_count === 0): ?>
                                <tr><td colspan="4" style="text-align:center; padding: 20px;">No errors</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (hasAccess('performance')): ?>
                <div class="info-table">
                    <h2 style="margin-bottom: 20px; font-size: 18px;">Load Times</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Network Type</th>
                                <th>Load Time</th>
                                <th>Device Specs</th>
                                <th>Sample Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): 
                                $payload = json_decode($row['payload'], true);
                                $perf = $payload['performance'] ?? [];
                                $static = $payload['static'] ?? [];
                            ?>
                            <tr>
                                <td><span style="padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($static['networkType'] ?? 'unknown') ?></span></td>
                                <td style="font-weight: bold;"><?= isset($perf['totalLoadTime']) ? round($perf['totalLoadTime'], 2) . "ms" : "N/A" ?></td>
                                <td><?= htmlspecialchars($static['platform'] ?? 'Unknown') ?></td>
                                <td><code><?= substr($row['session_id'], 0, 8) ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (hasAccess('performance') ||hasAccess('behavior')): ?>
                <div class="info-table">
                    <h2 style="margin-bottom: 20px; font-size: 18px;">User Engagement</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Total Time (Site)</th>
                                <th>Time (Current Page)</th>
                                <th>Idle Ratio</th>
                                <th>Latest URL</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): 
                                $payload = json_decode($row['payload'], true);
                                $act = $payload['activity'] ?? [];
                                $entry = $act['enteredPageAt'] ?? 0;
                                $exit = $act['leftPageAt'] ?? (time() * 1000);
                                $page_sec = max(0, ($exit - $entry) / 1000);
                                
                                $idle_ms = 0;
                                if (!empty($act['idleBreaks'])) {
                                    foreach ($act['idleBreaks'] as $b) { $idle_ms += $b['duration']; }
                                }
                                $idle_ratio = ($page_sec > 0) ? round(($idle_ms / 1000 / $page_sec) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><code><?= substr($row['session_id'], 0, 8) ?></code></td>
                                <td><?= $row['total_site_duration'] ?>s</td>
                                <td><?= round($page_sec, 1) ?>s</td>
                                <td><?= $idle_ratio ?>%</td>
                                <td style="font-size: 11px; color: #666;"><?= htmlspecialchars(basename($act['pageUrl'] ?? 'index')) ?></td>
                                <td><a href="view-session.php?id=<?= $row['session_id'] ?>" style="color: #007bff; text-decoration: none; font-weight: bold;">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (hasAccess('behavior')): ?>
                <div class="info-table">
                    <h2 style="margin-bottom: 20px; font-size: 18px;">User Activity</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Total Clicks</th>
                                <th>Key Activity</th>
                                <th>Screen Size</th>
                                <th>Page Sentiment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): 
                                $payload = json_decode($row['payload'], true);
                                $act = $payload['activity'] ?? [];
                                $static = $payload['static'] ?? [];
                                $clicks = count($act['clicks'] ?? []);
                                $keys = count($act['keyStrokes'] ?? []);
                            ?>
                            <tr>
                                <td><code><?= substr($row['session_id'], 0, 8) ?></code></td>
                                <td><strong><?= $clicks ?></strong></td>
                                <td><?= $keys ?> keystrokes</td>
                                <td><small><?= $static['screenWidth'] ?? '?' ?>x<?= $static['screenHeight'] ?? '?' ?></small></td>
                                <td>
                                    <?php if ($clicks > 15): ?>
                                        <span style="color: #721c24; padding: 2px 6px; border-radius: 4px; font-weight: bold;">Rage Clicks</span>
                                    <?php elseif ($clicks > 0 && ($keys == 0 && ($static['screenWidth'] ?? 1000) > 800)): ?>
                                        <span style="color: #856404; font-size: 12px;">Normal</span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </main>
    </body>
</html>