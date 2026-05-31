<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        session_regenerate_id(true); // Seguridad extra
        header('Location: index.php');
        exit;
    } else {
        $error = "Credenciales incorrectas.";
    }
}
?>
<!-- Formulario HTML -->
 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - NexusChat</title>
    <style>
        body { background: #0a0a0a; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .auth-box { background: #1f1f1f; padding: 30px; border-radius: 12px; border: 1px solid #333; width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 20px; color: #3b82f6; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; color: #fff; box-sizing: border-box; }
        input:focus { outline: none; border-color: #3b82f6; }
        button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600; }
        button:hover { background: #2563eb; }
        .error { color: #ef4444; text-align: center; margin-bottom: 15px; font-size: 14px; }
        .link { text-align: center; margin-top: 15px; color: #888; }
        .link a { color: #60a5fa; text-decoration: none; }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>🔗 NexusChat</h2>
        
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar sesión</button>
        </form>

        <p class="link">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
        <p class="link" style="margin-top: 15px; font-size: 13px;">
    <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
</p>
    </div>
</body>
</html>