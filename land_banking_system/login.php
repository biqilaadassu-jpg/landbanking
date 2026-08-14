<?php
require __DIR__ . '/config/config.php';

if (current_user()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare("SELECT * FROM users WHERE username=? AND active=1 LIMIT 1");
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        audit('LOGIN', 'USER', (int)$user['id']);
        redirect('index.php');
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Land Banking — Login</title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-page">
<div class="login-card">
    <div class="brand">LAND BANKING</div>
    <h1>Addis Ababa City</h1>
    <p>Code Enforcement Authority</p>
    <?php if ($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
    <form method="post">
        <label>Username</label>
        <input name="username" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button class="btn primary full">Sign In</button>
    </form>
</div>
</body>
</html>
