<?php
session_start();
require 'config.php';

/* ===============================
   HANDLE LOGIN
================================ */
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = md5($_POST['password']);

    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE email = ? AND password = ?
        LIMIT 1
    ");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();

    if ($user) {
        // SIMPAN SESSION
        $_SESSION['login'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['nama']  = $user['nama'];
        $_SESSION['role']  = $user['role'];

        if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] == 'siswa') {
                header("Location: siswa/dashboard.php");  
            } elseif ($user['role'] == 'teknisi') {
                header("Location: teknisi/dashboard.php");
            }   
            exit;
    } else {
        $error = "email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="user_id">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<?php if (isset($error)): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="email" placeholder="email" required>
    <br><br>
    <input type="password" name="password" placeholder="Password" required>
    <br><br>
    <button name="login">Login</button>
</form>

</body>
</html>
