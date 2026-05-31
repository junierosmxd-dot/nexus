<<?php
require 'config.php';

// Si ya está logueado, redirigir al feed
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validaciones
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Verificar si el email o username ya existen
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            $error = 'El nombre de usuario o correo electrónico ya está registrado.';
        } else {
            // Crear usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash]);
                
                $success = '✅ ¡Registro exitoso! Ahora puedes iniciar sesión.';
            } catch (PDOException $e) {
                $error = 'Error al registrar el usuario. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - NexusChat</title>
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .auth-box {
            background: #1f1f1f;
            padding: 40px;
            border-radius: 12px;
            border: 1px solid #333;
            width: 100%;
            max-width: 450px;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #3b82f6;
            font-size: 28px;
        }
        
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc2626;
        }
        
        .success {
            background: #f0fdf4;
            color: #16a34a;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #16a34a;
        }
        
        input {
            width: 100%;
            padding: 14px;
            margin-bottom: 15px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        input::placeholder {
            color: #666;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background: #2563eb;
        }
        
        .link {
            text-align: center;
            margin-top: 25px;
            color: #888;
            font-size: 14px;
        }
        
        .link a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 600;
        }
        
        .link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: -10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>🔗 NexusChat</h2>
        <p class="subtitle">Crea tu cuenta y únete a la comunidad</p>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input 
                type="text" 
                name="username" 
                placeholder="Nombre de usuario" 
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                required
                minlength="3"
                maxlength="50"
            >
            
            <input 
                type="email" 
                name="email" 
                placeholder="Correo electrónico" 
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
            >
            
            <input 
                type="password" 
                name="password" 
                placeholder="Contraseña" 
                required
                minlength="6"
            >
            <p class="password-requirements">Mínimo 6 caracteres</p>
            
            <input 
                type="password" 
                name="confirm_password" 
                placeholder="Confirmar contraseña" 
                required
                minlength="6"
            >
            
            <button type="submit">📝 Crear cuenta</button>
        </form>

        <p class="link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
    </div>
</body>
</html>