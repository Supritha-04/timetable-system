<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "timetable_system\admin\database.sql"; // change if different



$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>