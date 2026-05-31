<?php
require 'config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    header('Location: forgot_password.php');
    exit;
}

// Verificar token
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "Token inválido o expirado. Solicita uno nuevo.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Actualizar contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);
        
        $success = "✅ Contraseña actualizada. Ahora puedes iniciar sesión.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña - NexusChat</title>
    <style>
        body { background: #0a0a0a; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { background: #1f1f1f; padding: 40px; border-radius: 12px; border: 1px solid #333; width: 100%; max-width: 450px; }
        h2 { text-align: center; color: #3b82f6; margin-bottom: 10px; }
        input { width: 100%; padding: 14px; margin-bottom: 15px; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600; }
        .error { color: #ef4444; margin-bottom: 15px; padding: 10px; background: #fef2f2; border-radius: 6px; }
        .success { color: #10b981; margin-bottom: 15px; padding: 10px; background: #f0fdf4; border-radius: 6px; }
        .link { text-align: center; margin-top: 20px; color: #888; }
        .link a { color: #60a5fa; text-decoration: none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🔑 Nueva contraseña</h2>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
            <p class="link"><a href="login.php">Ir a iniciar sesión</a></p>
        <?php else: ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Nueva contraseña" required minlength="6">
                <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required minlength="6">
                <button type="submit">Cambiar contraseña</button>
            </form>
        <?php endif; ?>

        <p class="link"><a href="forgot_password.php">← Solicitar nuevo token</a></p>
    </div>
</body>
</html>