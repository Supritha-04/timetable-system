<?php
session_start();
include("../config/db.php");

if(isset($_SESSION['admin'])){
    header("Location: ../admin/dashboard.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user && password_verify($password,$user['password'])){
        $_SESSION['admin'] = $user['user_id'];
        header("Location: ../admin/dashboard.php");
        exit();
    } else {
        $error = "Invalid Credentials";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Admin Login</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>

body{
font-family:'Poppins',sans-serif;
background:linear-gradient(135deg,#3b82f6,#1e293b);
height:100vh;
display:flex;
align-items:center;
justify-content:center;
}

.login-card{
width:400px;
padding:30px;
border-radius:15px;
background:white;
box-shadow:0 10px 30px rgba(0,0,0,0.2);
}

.login-title{
text-align:center;
margin-bottom:20px;
font-weight:600;
}

.btn-login{
width:100%;
}

</style>

</head>

<body>

<div class="login-card">

<h3 class="login-title">
<i class="fa fa-calendar"></i> Timetable Admin
</h3>

<?php
if(isset($error)){
echo "<div class='alert alert-danger'>$error</div>";
}
?>

<form method="POST">

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<button type="submit" class="btn btn-primary btn-login">
<i class="fa fa-sign-in-alt"></i> Login
</button>

</form>

</div>

</body>
</html>