<?php
require 'config.php';
requireLogin();

$room_id = (int)($_GET['id'] ?? 0);

if (!$room_id) {
    die('<div style="text-align:center; padding:50px; color:#fff;"><h2>🚫 Sala no válida</h2><a href="rooms.php" style="color:#3b82f6;">Volver a salas</a></div>');
}

// Obtener info de la sala CON el nombre del creador
$stmt = $pdo->prepare("
    SELECT r.*, u.username as creator_name
    FROM rooms r
    JOIN users u ON r.created_by = u.id
    WHERE r.id = ?
");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    die('<div style="text-align:center; padding:50px; color:#fff;"><h2>🚫 Sala no encontrada</h2><a href="rooms.php" style="color:#3b82f6;">Volver a salas</a></div>');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($room['name']) ?> - NexusChat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; display: flex; flex-direction: column; height: 100vh; }
        
        .chat-header { 
            background: #1f1f1f; 
            padding: 15px 30px; 
            border-bottom: 1px solid #333; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .chat-header h1 { color: #3b82f6; font-size: 20px; margin: 0; }
        .chat-header .back-link { color: #fff; text-decoration: none; padding: 8px 16px; background: #2a2a2a; border-radius: 6px; font-size: 14px; }
        .chat-header .back-link:hover { background: #3b82f6; }
        
        .room-info { 
            background: #1f1f1f; 
            border: 1px solid #333; 
            border-radius: 12px; 
            padding: 15px 20px; 
            margin: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .room-info h2 { color: #fff; margin: 0 0 5px 0; font-size: 18px; }
        .room-info .desc { color: #888; font-size: 14px; }
        .room-info .creator { color: #3b82f6; font-size: 14px; font-weight: 600; }
        
        .chat-container { flex: 1; display: flex; flex-direction: column; max-width: 900px; margin: 0 auto; width: 100%; padding: 0 20px 20px; box-sizing: border-box; }
        #messages { flex: 1; overflow-y: auto; background: #151515; border-radius: 12px; padding: 15px; margin-bottom: 15px; border: 1px solid #333; }
        .msg { margin-bottom: 12px; padding: 10px; background: #1f1f1f; border-radius: 8px; border-left: 3px solid #3b82f6; }
        .msg.mine { border-left-color: #10b981; background: #1a2f23; }
        .msg .user { font-weight: bold; color: #3b82f6; font-size: 14px; }
        .msg .text { margin-top: 5px; line-height: 1.4; word-break: break-word; }
        .msg .time { font-size: 11px; color: #666; margin-top: 5px; }
        .msg img { max-width: 250px; max-height: 200px; border-radius: 8px; margin-top: 8px; cursor: pointer; display: block; }
        
        .input-area { display: flex; gap: 10px; }
        .input-area input { flex: 1; padding: 12px; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; color: #fff; font-size: 14px; }
        .input-area button { padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .input-area button:hover { background: #2563eb; }
        
        .upload-btn { background: #2a2a2a; border: 1px solid #444; color: #888; padding: 12px 16px; border-radius: 8px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
        .upload-btn:hover { background: #333; color: #3b82f6; }
        .preview-container { display: none; margin-bottom: 10px; padding: 8px; background: #1f1f1f; border-radius: 8px; }
        .preview-container img { max-height: 60px; border-radius: 6px; }
        .form-wrapper { display: flex; flex-direction: column; gap: 10px; }
        
        .no-messages { text-align: center; color: #666; padding: 40px; }
    </style>
</head>
<body>
    <div class="chat-header">
        <h1>🏫 <?= htmlspecialchars($room['name']) ?></h1>
        <a href="rooms.php" class="back-link">← Volver a salas</a>
    </div>

    <div class="chat-container">
        <div class="room-info">
            <div>
                <h2><?= htmlspecialchars($room['name']) ?></h2>
                <div class="desc"><?= htmlspecialchars($room['description'] ?: 'Sin descripción') ?></div>
            </div>
            <div class="creator">Creada por @<?= htmlspecialchars($room['creator_name']) ?></div>
        </div>
        
        <div id="messages">
            <div class="no-messages">No hay mensajes aún</div>
        </div>
        
        <form class="form-wrapper" id="chatForm" enctype="multipart/form-data">
            <div class="preview-container" id="imagePreview">
                <img id="previewImg" src="" alt="Preview">
            </div>
            <div class="input-area">
                <input type="file" id="imageInput" accept="image/*" style="display: none;">
                <label for="imageInput" class="upload-btn" title="Enviar imagen">📷</label>
                <input type="text" id="msgInput" placeholder="Escribe un mensaje..." autocomplete="off">
                <button type="submit">Enviar</button>
            </div>
        </form>
    </div>

    <script>
        const currentUserId = <?= $_SESSION['user_id'] ?>;
        const roomId = <?= $room_id ?>;
        
        // Preview de imagen
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Cargar mensajes
        async function loadMessages() {
            try {
                const res = await fetch('api/fetch_messages.php?id=' + roomId);
                const msgs = await res.json();
                const container = document.getElementById('messages');
                
                if (!msgs || msgs.length === 0) {
                    container.innerHTML = '<div class="no-messages">No hay mensajes aún</div>';
                    return;
                }
                
                container.innerHTML = msgs.map(msg => {
                    const isMine = msg.user_id == currentUserId;
                    return `
                        <div class="msg ${isMine ? 'mine' : ''}">
                            <div class="user">@${msg.username || 'Usuario'}</div>
                            <div class="text">${msg.content || ''}</div>
                            ${msg.image ? `<img src="${msg.image}" onclick="window.open(this.src)">` : ''}
                            <div class="time">${new Date(msg.created_at).toLocaleString()}</div>
                        </div>
                    `;
                }).join('');
                
                container.scrollTop = container.scrollHeight;
            } catch (err) {
                console.error('Error cargando mensajes:', err);
            }
        }
        
        // Enviar mensaje
        document.getElementById('chatForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const content = document.getElementById('msgInput').value.trim();
            const fileInput = document.getElementById('imageInput');
            const file = fileInput.files[0];
            
            if (!content && !file) {
                alert('Escribe un mensaje o selecciona una imagen');
                return;
            }
            
            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('content', content);
            if (file) formData.append('image', file);
            
            try {
                const res = await fetch('api/send_message.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                console.log('Respuesta:', data);
                
                if (data.success) {
                    document.getElementById('msgInput').value = '';
                    fileInput.value = '';
                    document.getElementById('imagePreview').style.display = 'none';
                    loadMessages();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo enviar'));
                }
            } catch (err) {
                console.error('Error:', err);
                alert('Error de conexión');
            }
        });
        
        // Cargar mensajes al inicio y cada 3 segundos
        loadMessages();
        setInterval(loadMessages, 3000);
    </script>
</body>
</html>