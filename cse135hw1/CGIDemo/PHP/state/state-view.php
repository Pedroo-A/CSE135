<?php
session_start();

echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo" <head><title>View State Management</title></head>\n";
echo "<body>\n";
echo "<h1>Screen 2: View Data</h1>\n";

if (isset($_SESSION['savedMessage'])) {
    $data = htmlspecialchars($_SESSION['savedMessage']);
    echo "<p>Data:\n $data</p>\n";
} else {
    echo "<p>No data found in the session.</p>\n";
}

echo "<hr>\n";
echo "<a href='state-php.html'>Back to Input Screen</a>\n\n\n";

echo "<form action='state-handler.php' method='POST' style='margin-top: 20px;'>\n";
echo "    <input type='hidden' name='action' value='clear'>\n";
echo "    <button type='submit'>Destroy Session</button>\n";
echo "</form>\n";

echo "</body>\n</html>";
?>