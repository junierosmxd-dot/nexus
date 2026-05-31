<?php
require 'config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $name = trim($_POST['room_name']);
    $desc = trim($_POST['room_desc']);
    if (empty($name)) {
        $error = 'El nombre es obligatorio.';
    } else {
        $code = bin2hex(random_bytes(4));
        try {
            $pdo->prepare("INSERT INTO rooms (name, description, created_by, invite_code) VALUES (?, ?, ?, ?)")
                ->execute([$name, $desc, $_SESSION['user_id'], $code]);
            header('Location: rooms.php?msg=creada');
            exit;
        } catch (Exception $e) {
            $error = 'Error al crear la sala.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_code'])) {
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE invite_code = ?");
    $stmt->execute([trim($_POST['invite_code_input'])]);
    $room = $stmt->fetch();
    if ($room) {
        header('Location: chat.php?id=' . $room['id']);
        exit;
    } else {
        $error = 'Código no válido.';
    }
}

$rooms = $pdo->query("
    SELECT r.*, u.username as creator, COUNT(m.id) as msg_count 
    FROM rooms r 
    JOIN users u ON r.created_by = u.id 
    LEFT JOIN messages m ON r.id = m.room_id 
    GROUP BY r.id 
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salas - NexusChat</title>
    <style>
        /* FONDO GENERAL OSCURO */
        body {
            background-color: #0a0a0a;
            color: #ffffff;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        /* ESTILOS ESPECÍFICOS DE ROOMS (Prefijo rm-) */
        .rm-container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .rm-title { color: #3b82f6; margin-bottom: 20px; font-size: 24px; }
        
        .rm-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .rm-tab { 
            flex: 1; 
            padding: 12px; 
            text-align: center; 
            background: #1f1f1f; 
            border: 1px solid #333; 
            border-radius: 8px; 
            cursor: pointer;
            color: #fff;
            font-weight: 600;
            transition: 0.3s;
        }
        .rm-tab:hover { background: #2a2a2a; }
        .rm-tab.active { background: #3b82f6; border-color: #3b82f6; }
        
        .rm-card { background: #1f1f1f; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 15px; }
        .rm-card h3 { color: #3b82f6; margin-bottom: 15px; }
        
        .rm-input, .rm-textarea { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 10px; 
            background: #2a2a2a; 
            border: 1px solid #444; 
            border-radius: 8px; 
            color: #fff; 
            box-sizing: border-box;
            font-family: inherit;
        }
        .rm-textarea { resize: vertical; min-height: 60px; }
        
        .rm-btn { 
            background: #3b82f6; 
            color: #fff; 
            border: none; 
            padding: 12px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            width: 100%;
            font-size: 15px;
        }
        .rm-btn:hover { background: #2563eb; }
        
        .rm-error { color: #ef4444; background: #450a0a; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #ef4444; }
        .rm-success { color: #10b981; background: #064e3b; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #10b981; }
        
        .rm-room-link { display: block; text-decoration: none; color: inherit; }
        .rm-room-link:hover .rm-card { border-color: #3b82f6; }
        
        .rm-room-name { color: #3b82f6; margin-bottom: 5px; font-size: 18px; font-weight: 600; }
        .rm-room-desc { color: #ccc; margin: 5px 0; }
        .rm-room-meta { color: #888; font-size: 14px; }
        
        .rm-invite-box { 
            background: #1a2f3a; 
            border: 1px solid #2563eb; 
            padding: 12px; 
            border-radius: 6px; 
            margin-top: 12px; 
            font-size: 13px;
            word-break: break-all;
        }
        .rm-invite-box code { color: #60a5fa; background: #0f172a; padding: 2px 6px; border-radius: 4px; }
        .rm-invite-box strong { color: #93c5fd; }
        
        .rm-section-title { color: #3b82f6; margin: 25px 0 15px; font-size: 20px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="rm-container">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'creada'): ?>
            <div class="rm-success">✅ Sala creada exitosamente.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="rm-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2 class="rm-title">🏫 Salas de Estudio</h2>

        <div class="rm-tabs">
            <div class="rm-tab active" onclick="rmShowTab('create')">Crear Sala</div>
            <div class="rm-tab" onclick="rmShowTab('join')">Unirse con Código</div>
        </div>

        <div id="rm-create-tab">
            <div class="rm-card">
                <h3>📝 Crear nueva sala</h3>
                <form method="POST">
                    <input type="text" name="room_name" class="rm-input" placeholder="Nombre (ej: Cálculo I - Grupo A)" required>
                    <textarea name="room_desc" class="rm-textarea" placeholder="Descripción o tema..."></textarea>
                    <button type="submit" name="create_room" class="rm-btn">Crear Sala</button>
                </form>
            </div>
        </div>

        <div id="rm-join-tab" style="display:none;">
            <div class="rm-card">
                <h3>🔗 Unirse por invitación</h3>
                <form method="POST">
                    <input type="text" name="invite_code_input" class="rm-input" placeholder="Pega aquí el código o link de invitación" required>
                    <button type="submit" name="join_code" class="rm-btn">Entrar a la sala</button>
                </form>
            </div>
        </div>

        <h3 class="rm-section-title">📚 Salas disponibles</h3>
        <?php if (empty($rooms)): ?>
            <div style="text-align:center; color:#666; padding:30px;">No hay salas creadas aún.</div>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <div class="rm-card">
                    <a href="chat.php?id=<?= $room['id'] ?>" class="rm-room-link">
                        <div class="rm-room-name"><?= htmlspecialchars($room['name']) ?></div>
                        <div class="rm-room-desc"><?= htmlspecialchars($room['description'] ?: 'Sin descripción') ?></div>
                        <div class="rm-room-meta">
                            Creada por: @<?= htmlspecialchars($room['creator']) ?> | 💬 <?= $room['msg_count'] ?> mensajes
                        </div>
                    </a>
                    <?php if ($room['created_by'] == $_SESSION['user_id']): ?>
                        <div class="rm-invite-box">
                            🔗 <strong>Link de invitación:</strong><br>
                            <code>http://localhost:8080/nexus/rooms.php?code=<?= $room['invite_code'] ?></code><br><br>
                            <strong>Código manual:</strong> <?= $room['invite_code'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function rmShowTab(tab) {
            document.getElementById('rm-create-tab').style.display = tab === 'create' ? 'block' : 'none';
            document.getElementById('rm-join-tab').style.display = tab === 'join' ? 'block' : 'none';
            document.querySelectorAll('.rm-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        // Auto-join si viene por URL con ?code=xxxx
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('code')) {
            document.querySelector('input[name="invite_code_input"]').value = urlParams.get('code');
            rmShowTab('join');
        }
    </script>
</body>
</html>