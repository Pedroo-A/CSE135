<?php
$dateTime = date('Y-m-d H:i:s');
$userIP = $_SERVER['REMOTE_ADDR'];

echo "<!DOCTYPE html>\n";
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Hello HTML PHP</title>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <h1>Greeting from PEDRO</h1>\n";
echo "    <p><b>Language used: PHP</p>\n";
echo "    <p><b>Page generated at:</b> $dateTime</p>\n";
echo "    <p><b>Your IP Address:</b> $userIP</p>\n";
echo "</body>\n";
echo "</html>";
?>