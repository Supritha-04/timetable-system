<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "timatable_system"; // change if different

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
