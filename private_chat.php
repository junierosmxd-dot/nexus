<?php
require 'config.php';
requireLogin();

$receiver_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT id, username, profile_pic FROM users WHERE id = ? AND id != ?");
$stmt->execute([$receiver_id, $_SESSION['user_id']]);
$receiver = $stmt->fetch();

if (!$receiver) {
    die('<div style="text-align:center; padding:50px; color:#fff;"><h2>🚫 Usuario no encontrado</h2><a href="index.php" style="color:#3b82f6;">Volver al inicio</a></div>');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con <?= htmlspecialchars($receiver['username']) ?></title>
    <style>
        body { background: #0a0a0a; color: #fff; font-family: sans-serif; margin: 0; display: flex; flex-direction: column; height: 100vh; }
        nav { background: #1f1f1f; padding: 15px 30px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
        nav h1 { font-size: 20px; color: #3b82f6; margin: 0; }
        nav a { color: #fff; text-decoration: none; margin-left: 15px; }
        .chat-container { flex: 1; display: flex; flex-direction: column; max-width: 800px; margin: 0 auto; width: 100%; padding: 20px; box-sizing: border-box; }
        #messages { flex: 1; overflow-y: auto; background: #151515; border-radius: 12px; padding: 15px; margin-bottom: 15px; border: 1px solid #333; }
        .msg { margin-bottom: 12px; padding: 10px; background: #1f1f1f; border-radius: 8px; border-left: 3px solid #3b82f6; }
        .msg.mine { border-left-color: #10b981; background: #1a2f23; }
        .msg .user { font-weight: bold; color: #3b82f6; font-size: 14px; }
        .msg .text { margin-top: 5px; line-height: 1.4; word-break: break-word; }
        .msg .time { font-size: 11px; color: #666; margin-top: 5px; }
        .msg img { max-width: 250px; max-height: 200px; border-radius: 8px; margin-top: 8px; cursor: pointer; display: block; }
        .input-area { display: flex; gap: 10px; }
        .input-area input { flex: 1; padding: 12px; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; color: #fff; }
        .input-area button { padding: 12px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .header-info { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .avatar-small { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .upload-btn { background: #2a2a2a; border: 1px solid #444; color: #888; padding: 12px 16px; border-radius: 8px; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
        .upload-btn:hover { background: #333; color: #3b82f6; }
        .preview-container { display: none; margin-bottom: 10px; padding: 8px; background: #1f1f1f; border-radius: 8px; }
        .preview-container img { max-height: 60px; border-radius: 6px; }
        .form-wrapper { display: flex; flex-direction: column; gap: 10px; }
    </style>
</head>
<body>
    <nav>
        <h1>💬 Chat Privado</h1>
        <a href="index.php">← Volver</a>
    </nav>

    <div class="chat-container">
        <div class="header-info">
            <img src="assets/uploads/avatars/<?= htmlspecialchars($receiver['profile_pic']) ?>" class="avatar-small" onerror="this.src='https://via.placeholder.com/40'">
            <h2 style="margin:0;">Conectado con @<?= htmlspecialchars($receiver['username']) ?></h2>
        </div>
        <div id="messages"></div>
        
        <form class="form-wrapper" id="privateChatForm" enctype="multipart/form-data">
            <div class="preview-container" id="imagePreview">
                <img id="previewImg" src="" alt="Preview">
            </div>
            <div class="input-area">
                <input type="file" id="imageInput" accept="image/*" style="display: none;">
                <label for="imageInput" class="upload-btn" title="Enviar imagen">📷</label>
                <input type="text" id="privateMsgInput" placeholder="Escribe un mensaje..." autocomplete="off">
                <button type="submit">Enviar</button>
            </div>
        </form>
    </div>

    <script>
        const currentUserId = <?= $_SESSION['user_id'] ?>;
        const receiverId = <?= $receiver_id ?>;
        
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
                const res = await fetch('api/fetch_private_msgs.php?id=' + receiverId);
                const msgs = await res.json();
                const container = document.getElementById('messages');
                
                if (!msgs || msgs.length === 0) {
                    container.innerHTML = '<div style="text-align:center; color:#666; padding:20px;">No hay mensajes aún</div>';
                    return;
                }
                
                container.innerHTML = msgs.map(msg => {
                    const isMine = msg.sender_id == currentUserId;
                    return `
                        <div class="msg ${isMine ? 'mine' : ''}">
                            <div class="user">@${msg.sender_name || 'Usuario'}</div>
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
        document.getElementById('privateChatForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const content = document.getElementById('privateMsgInput').value.trim();
            const fileInput = document.getElementById('imageInput');
            const file = fileInput.files[0];
            
            if (!content && !file) {
                alert('Escribe un mensaje o selecciona una imagen');
                return;
            }
            
            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('content', content);
            if (file) formData.append('image', file);
            
            try {
                const res = await fetch('api/send_private_msg.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                console.log('Respuesta:', data);
                
                if (data.success) {
                    document.getElementById('privateMsgInput').value = '';
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