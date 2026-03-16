<?php
require_once('auth.php');

// accessible only to super admin
if ($_SESSION['role'] !== 'super_admin') {
    header('Location: dashboard.php');
    exit("Access Denied");
}

$host = 'localhost';
$db   = 'analytics_db';
$user = 'collector_bot';
$pass = 'Pedro3344752';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Create mew user
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
        $new_user = $_POST['username'];
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $new_role = $_POST['role'];
        
        // Combine selected permissions into a comma-separated string
        $permissions = isset($_POST['sections']) ? implode(',', $_POST['sections']) : '';
        if ($new_role === 'super_admin') $permissions = 'all';
        if ($new_role === 'viewer') $permissions = 'reports';

        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, allowed_sections) VALUES (?, ?, ?, ?)");
        $stmt->execute([$new_user, $new_pass, $new_role, $permissions]);
        $msg = "New user created";
    }

    //delete user
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        // prevent deleting current super user
        if ($id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: manage-users.php?msg=Deleted');
            exit();
        }
    }

    //fetch all users
    $users = $pdo->query("SELECT * FROM users ORDER BY role ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>User Management</title>
        <style>
            :root { --navbar-width: 240px; }
            body { font-family: sans-serif; background-color: #f8f9fa; display: flex; margin: 0; }

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

            main { 
                margin-left: var(--navbar-width); 
                padding: 40px; 
                width: 100%; 
            }
            .card { 
                background: white; 
                padding: 25px; 
                border-radius: 8px; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
                margin-bottom: 30px; 
            }
            
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 20px; 
            }
            th, td { 
                text-align: left; 
                padding: 12px; 
                border-bottom: 1px solid #dee2e6; 
            }
            
            .form-create { margin-bottom: 15px; }
            label { 
                display: block; 
                margin-bottom: 5px; 
                font-weight: bold; 
                font-size: 14px; 
            }
            input, select { 
                padding: 8px; 
                width: 100%; 
                max-width: 300px; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
            }
            .btn { 
                padding: 10px 20px; 
                border: none; 
                border-radius: 4px; 
                cursor: pointer; 
                color: white; 
            }
            .btn-add { background: #28a745; }
            .btn-del { background: #dc3545; font-size: 12px; padding: 5px 10px; }
            .badge { 
                padding: 4px 8px; 
                border-radius: 12px; 
                font-size: 11px; font-weight: bold; 
                text-transform: uppercase; 
            }
            .super_admin { background: #fff3cd; color: #856404; }
            .analyst { background: #d1ecf1; color: #0c5460; }
            .viewer { background: #e2e3e5; color: #383d41; }
        </style>
    </head>
    <body>

        <nav>   
            <h2>CSE 135 Admin</h2>
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="logout.php" style="margin-top: 50px; color: #f43131;">Logout</a>
        </nav>

        <main>
            <h1>User Management</h1>
            
            <?php if (isset($msg)): ?>
                <p style="color: green; font-weight: bold;"><?= $msg ?></p>
            <?php endif; ?>

            <div class="card">
                <h3>Create New User</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-create">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-create">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-create">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="viewer">Viewer</option>
                            <option value="analyst" selected>Analyst</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-create">
                        <label>Permissions (For analysts)</label>
                        <input type="checkbox" name="sections[]" value="performance" checked> Performance Data<br>
                        <input type="checkbox" name="sections[]" value="behavior"> Behavioral Data<br>
                        <input type="checkbox" name="sections[]" value="diagnostic"> Diagnostic Data (error logs)
                    </div>
                    <button type="submit" class="btn btn-add">Create User</button>
                </form>
            </div>

            <div class="card">
                <h3>Existing Users</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Permissions</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><span class="badge <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                            <td><code><?= htmlspecialchars($u['allowed_sections']) ?></code></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-del" onclick="return confirm('Delete this user?')">Delete</a>
                                <?php else: ?>
                                    <small>(Current Session)</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>

    </body>
</html>