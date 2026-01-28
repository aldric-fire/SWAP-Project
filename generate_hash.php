<?php
$password = 'password123';  // Change this to whatever password you want
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: " . htmlspecialchars($password) . "<br>";
echo "Hash: " . htmlspecialchars($hash) . "<br>";
echo "<br>Copy the hash above and use it in MySQL.";
?>
