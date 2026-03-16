<?php
echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head><title>Environment Variables - PHP</title></head>\n";
echo "<body>\n";
echo "    <h1>Environment Variables (PHP):</h1>\n";
echo "    <table border='1' style='border-collapse: collapse;'>\n";
echo "        <tr><th>Variable</th><th>Value</th></tr>\n";

foreach ($_SERVER as $key => $value) {
    echo "        <tr>";
    echo "<td>" . htmlspecialchars($key) . "</td>";
    echo "<td>" . htmlspecialchars($value) . "</td>";
    echo "</tr>\n";
}

echo "    </table>\n";
echo "</body>\n";
echo "</html>";
?>