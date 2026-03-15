<?php
session_start();
include("../config/db.php");
<?php
session_start();

// Only redirect if NOT logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // ✅ Use correct column name (id instead of user_id if needed)
        $_SESSION['admin'] = $user['id']; 
        header("Location: ../admin/dashboard.php");
        exit();
    } else {
        $error = "Invalid Credentials";
    }
}
?>