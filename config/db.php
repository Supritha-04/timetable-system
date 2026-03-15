<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "timetable_system"; // change if different

echo "Database being used: " . $database;
exit;

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>