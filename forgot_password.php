<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';
$token_url = '';
$user_found = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Ingresa tu correo electrónico.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user_found = true;
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
                $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL");
            } catch(Exception $e) {}
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            $token_url = "http://localhost:8080/nexus/reset_password.php?token=$token";
            $message = "✅ Enlace de recuperación generado para: <strong>" . htmlspecialchars($user['username']) . "</strong>";
        } else {
            $error = "❌ El correo <strong>" . htmlspecialchars($email) . "</strong> no está registrado.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - NexusChat</title>
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .box {
            background: #1f1f1f;
            padding: 40px;
            border-radius: 12px;
            border: 1px solid #333;
            width: 100%;
            max-width: 600px;
        }
        h2 { text-align: center; color: #3b82f6; margin-bottom: 10px; }
        .desc { text-align: center; color: #888; margin-bottom: 30px; font-size: 14px; }
        input {
            width: 100%; padding: 14px; margin-bottom: 15px;
            background: #2a2a2a; border: 1px solid #444; border-radius: 8px;
            color: #fff; box-sizing: border-box;
        }
        input:focus { outline: none; border-color: #3b82f6; }
        button {
            width: 100%; padding: 14px; background: #3b82f6; color: white;
            border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;
        }
        button:hover { background: #2563eb; }
        .error { 
            color: #ef4444; margin-bottom: 15px; padding: 15px; 
            background: #450a0a; border-radius: 6px; border-left: 4px solid #ef4444;
        }
        .success { 
            color: #10b981; margin-bottom: 15px; padding: 15px; 
            background: #064e3b; border-radius: 6px; border-left: 4px solid #10b981;
        }
        .token-box {
            background: #0a0a0a;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            word-break: break-all;
            overflow-wrap: anywhere;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #60a5fa;
            max-width: 100%;
            box-sizing: border-box;
            cursor: pointer;
            user-select: all;
        }
        .token-box:hover {
            border-color: #60a5fa;
            background: #111;
        }
        .token-info { 
            font-size: 13px; 
            color: #888; 
            margin-top: 10px;
            padding: 10px;
            background: #1f1f1f;
            border-radius: 6px;
        }
        .copy-hint {
            font-size: 12px;
            color: #3b82f6;
            margin-top: 5px;
            text-align: center;
        }
        .link { text-align: center; margin-top: 25px; color: #888; }
        .link a { color: #60a5fa; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🔐 Recuperar contraseña</h2>
        <p class="desc">Ingresa tu correo para generar el enlace de recuperación</p>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success">
                <div style="font-size: 16px; margin-bottom: 10px;"><?= $message ?></div>
                
                <?php if ($token_url): ?>
                    <div style="margin: 15px 0;">
                        <div class="copy-hint">📋 Haz clic en el enlace para copiarlo</div>
                        <div class="token-box" onclick="navigator.clipboard.writeText(this.textContent); alert('¡Enlace copiado!')">
                            <?= htmlspecialchars($token_url) ?>
                        </div>
                    </div>
                    
                    <div class="token-info">
                        <strong>⏰ Instrucciones:</strong><br>
                        1. Haz clic en el enlace azul de arriba para copiarlo<br>
                        2. Pégalo en tu navegador<br>
                        3. Escribe tu nueva contraseña<br>
                        <br>
                        <em>El enlace expira en 1 hora</em>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="tu@email.com" required>
            <button type="submit">Generar enlace</button>
        </form>

        <p class="link"><a href="login.php">← Volver al login</a></p>
    </div>
</body>
</html>