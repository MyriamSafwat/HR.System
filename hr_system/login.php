<?php
session_start();
require_once "config.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->execute([
        ':email' => $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        header("Location: dashboard.php");
        exit;

    } else {

        $error = "Invalid Email or Password";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login</title>

<style>

body{
    margin:0;
    font-family:Arial;
    background:#f4f6f8;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.login-box{
    width:400px;
    background:#fff;
    padding:30px;
    border-radius:16px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

h1{
    margin-top:0;
    text-align:center;
}

input{
    width:100%;
    padding:12px;
    margin-top:10px;
    border:1px solid #ddd;
    border-radius:10px;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:12px;
    margin-top:15px;
    border:none;
    border-radius:10px;
    background:#111827;
    color:#fff;
    cursor:pointer;
}

.error{
    color:red;
    margin-top:10px;
}

</style>
</head>
<body>

<div class="login-box">

    <h1>AL WATAD HRMS</h1>

    <?php if($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="email"
            name="email"
            placeholder="Email"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

</div>

</body>
</html>