<?php
require_once __DIR__ . '/config/session.php';

echo "Session Status: " . session_status() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "SESSION_EXPIRED: " . ($SESSION_EXPIRED ? 'true' : 'false') . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "<br>Session Contents:<br>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";
?>
