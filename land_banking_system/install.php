<?php
require __DIR__ . '/config/config.php';

try {
    $pdo = db();

    $exists = $pdo->query("SELECT COUNT(*) c FROM users WHERE username='admin'")->fetch()['c'];
    if (!$exists) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, full_name, password_hash, role) VALUES ('admin','System Administrator',?,'ADMIN')"
        );
        $stmt->execute([$hash]);
    }

    echo '<h2>Installation completed.</h2>';
    echo '<p>Login: <b>admin</b> / <b>admin123</b></p>';
    echo '<p><a href="login.php">Open Login</a></p>';
    echo '<p><b>Delete or rename install.php after installation.</b></p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>' . e($e->getMessage()) . '</pre>';
}
