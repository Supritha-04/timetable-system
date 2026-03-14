<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['admin'])){
    header("Location: ../auth/login.php");
    exit();
}

?>