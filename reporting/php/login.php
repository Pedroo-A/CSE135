<?php
session_start();

$host = 'localhost';
$db   = 'analytics_db';
$user = 'collector_bot';
$pass = 'Pedro3344752';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && password_verify($password, $userData['password'])) {
            // Regeneration prevents Session Fixation attacks
            session_regenerate_id();
            
            $_SESSION['auth'] = true;
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role'] = $userData['role'];
            $_SESSION['allowed_sections'] = $userData['allowed_sections'];
            // Convert 'performance,behavior' into an array for easy checking later
            $_SESSION['sections'] = explode(',', $userData['allowed_sections']);

            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Incorrect username or password.";
        }
    } catch (PDOException $e) {
        $error = "Database connection error.";
    }
}
?>
<!DOCTYPE html>
<html>
    <head><title>Login</title></head>
    <body style="font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5;">
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <h2>Reporting Login</h2>
            <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required style="display:block; margin: 10px 0; padding: 8px; width: 200px;"><br>
                <input type="password" name="password" placeholder="Password" required style="display:block; margin: 10px 0; padding: 8px; width: 200px;"><br>
                <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Login</button>
            </form>
        </div>
    </body>
</html>