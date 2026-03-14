<?php
include("config/db.php");

$hash = password_hash("admin123", PASSWORD_DEFAULT);

$conn->query("INSERT INTO users(name,email,password,role)
VALUES('Admin','admin@vnrvjiet.ac.in','$hash','admin')");

echo "Admin Created";
?>
