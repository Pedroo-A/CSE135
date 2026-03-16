<?php
session_start();

$action = $_POST['action'] ?? '';

//save server-side session
if ($action === 'save' && isset($_POST['userInfo'])) {
    $_SESSION['savedMessage'] = $_POST['userInfo'];
    header("Location: state-view.php");
    exit();
} 
 //destroy server-side session
if ($action === 'clear') {
    session_unset();
    session_destroy();
    header("Location: state-php.html");
    exit();
}
header("Location: state-php.html");
exit();
?>
