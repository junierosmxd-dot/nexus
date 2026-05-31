<?php
require 'config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $target_dir = "assets/uploads/avatars/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $ext = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
    $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_dir . $new_name)) {
        $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$new_name, $user_id]);
        $success = "✅ Foto actualizada."; $user['profile_pic'] = $new_name;
    } else { $error = "❌ Error al subir."; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_username'])) {
    $new_user = trim($_POST['new_username']);
    if (strlen($new_user) >= 3) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$new_user, $user_id]);
        if (!$check->fetch()) {
            $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$new_user, $user_id]);
            $_SESSION['username'] = $new_user; $success = "✅ Usuario cambiado."; $user['username'] = $new_user;
        } else { $error = "❌ Usuario en uso."; }
    } else { $error = "❌ Mínimo 3 caracteres."; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    if (password_verify($_POST['current_password'], $user['password_hash'])) {
        if (strlen($_POST['new_password']) >= 6) {
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $user_id]);
            $success = "✅ Contraseña actualizada.";
        } else { $error = "❌ Mínimo 6 caracteres."; }
    } else { $error = "❌ Contraseña actual incorrecta."; }
}

// Obtener posts CON likes y comentarios
$posts_stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_pic,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$posts_stmt->execute([$user_id]);
$my_posts = $posts_stmt->fetchAll();

// Obtener comentarios para cada post (SIN referencia)
foreach ($my_posts as $key => $post) {
    $comments_stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_pic 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC
    ");
    $comments_stmt->execute([$post['id']]);
    $my_posts[$key]['comments'] = $comments_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - <?= htmlspecialchars($user['username']) ?></title>
    <style>
        body { background-color: #0a0a0a; color: #ffffff; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .pf-container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        .pf-header { background: #1f1f1f; border: 1px solid #333; border-radius: 12px; padding: 30px; text-align: center; margin-bottom: 25px; }
        .pf-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #3b82f6; margin-bottom: 15px; }
        .pf-username { font-size: 24px; font-weight: bold; color: #fff; }
        .pf-email { color: #888; margin-bottom: 20px; }
        .pf-card { background: #1f1f1f; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .pf-card h3 { color: #3b82f6; margin-bottom: 15px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .pf-input { width: 100%; padding: 12px; margin-bottom: 12px; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; color: #fff; box-sizing: border-box; }
        .pf-btn { background: #3b82f6; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .pf-btn:hover { background: #2563eb; }
        .pf-alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .pf-alert-ok { background: #064e3b; color: #10b981; border: 1px solid #10b981; }
        .pf-alert-err { background: #450a0a; color: #ef4444; border: 1px solid #ef4444; }
        
        .pf-post { background: #1f1f1f; border: 1px solid #333; border-radius: 8px; padding: 15px; margin-bottom: 20px; position: relative; }
        .pf-post-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .pf-post-info { display: flex; align-items: center; gap: 10px; }
        .pf-post-info img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #3b82f6; }
        .pf-author { font-weight: 600; color: #fff; }
        .pf-time { font-size: 12px; color: #888; }
        .pf-content { margin: 10px 0; line-height: 1.5; color: #e5e5e5; }
        .pf-post-img { width: 100%; border-radius: 8px; margin-top: 10px; }
        
        .pf-stats { 
            display: flex; 
            gap: 20px; 
            padding: 12px 0; 
            border-top: 1px solid #333; 
            border-bottom: 1px solid #333; 
            margin-top: 12px;
            color: #888;
            font-size: 14px;
        }
        .pf-stat { display: flex; align-items: center; gap: 6px; }
        
        .pf-dots { cursor: pointer; font-size: 20px; color: #888; padding: 5px; border-radius: 50%; }
        .pf-dots:hover { background: #2a2a2a; }
        .pf-menu { position: absolute; right: 15px; top: 35px; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; min-width: 150px; display: none; z-index: 100; }
        .pf-menu.show { display: block; }
        .pf-menu-item { padding: 12px 15px; cursor: pointer; color: #fff; }
        .pf-menu-item:hover { background: #3b82f6; }
        .pf-menu-del { color: #ef4444; }
        .pf-menu-del:hover { background: #ef4444; color: #fff; }
        
        .pf-comments { margin-top: 12px; background: #151515; padding: 12px; border-radius: 6px; }
        .pf-comment { display: flex; gap: 10px; margin-bottom: 10px; }
        .pf-comment img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .pf-comment-bubble { background: #2a2a2a; padding: 8px 12px; border-radius: 12px; flex: 1; }
        .pf-comment-author { font-weight: 600; font-size: 13px; color: #3b82f6; margin-bottom: 2px; }
        .pf-comment-text { font-size: 14px; color: #e5e5e5; }
        .pf-comment-time { font-size: 11px; color: #666; margin-top: 4px; }
        .pf-no-comments { color: #666; text-align: center; padding: 10px; font-size: 13px; }
        
        .pf-editing { background: #2a2a2a; padding: 10px; border-radius: 6px; }
        .pf-editing textarea { width: 100%; background: #1a1a1a; border: 1px solid #444; border-radius: 6px; color: #fff; padding: 10px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="pf-container">
        <?php if ($success): ?><div class="pf-alert pf-alert-ok"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="pf-alert pf-alert-err"><?= $error ?></div><?php endif; ?>

        <div class="pf-header">
            <img src="assets/uploads/avatars/<?= htmlspecialchars($user['profile_pic'] ?? 'default.png') ?>" class="pf-avatar" onerror="this.src='https://via.placeholder.com/120'">
            <div class="pf-username">@<?= htmlspecialchars($user['username']) ?></div>
            <div class="pf-email"><?= htmlspecialchars($user['email']) ?></div>
        </div>

        <div class="pf-card">
            <h3>📸 Cambiar Foto</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="avatar" class="pf-input" accept="image/*" required>
                <button type="submit" class="pf-btn">Actualizar</button>
            </form>
        </div>

        <div class="pf-card">
            <h3>✏️ Cambiar Usuario</h3>
            <form method="POST">
                <input type="text" name="new_username" class="pf-input" placeholder="Nuevo usuario" required minlength="3">
                <button type="submit" class="pf-btn">Guardar</button>
            </form>
        </div>

        <div class="pf-card">
            <h3>🔒 Cambiar Contraseña</h3>
            <form method="POST">
                <input type="password" name="current_password" class="pf-input" placeholder="Actual" required>
                <input type="password" name="new_password" class="pf-input" placeholder="Nueva (mín. 6)" required minlength="6">
                <button type="submit" class="pf-btn">Actualizar</button>
            </form>
        </div>

        <h2 style="color:#3b82f6; margin: 30px 0 20px;">📝 Mis Publicaciones</h2>
        <?php if (empty($my_posts)): ?>
            <div style="text-align:center; color:#666; padding:30px;">No has publicado nada.</div>
        <?php else: ?>
            <?php foreach ($my_posts as $post): 
                $post_avatar = !empty($post['profile_pic']) ? $post['profile_pic'] : 'default.png';
            ?>
                <div class="pf-post" data-id="<?= $post['id'] ?>">
                    <div class="pf-post-head">
                        <div class="pf-post-info">
                            <img src="assets/uploads/avatars/<?= htmlspecialchars($post_avatar) ?>" onerror="this.src='https://via.placeholder.com/40'">
                            <div><div class="pf-author">@<?= htmlspecialchars($post['username']) ?></div><div class="pf-time"><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div></div>
                        </div>
                        <div class="pf-dots" onclick="toggleMenu(<?= $post['id'] ?>)">⋮</div>
                        <div class="pf-menu" id="menu-<?= $post['id'] ?>">
                            <div class="pf-menu-item" onclick="editPost(<?= $post['id'] ?>, '<?= htmlspecialchars(str_replace("'", "\'", $post['content']), ENT_QUOTES) ?>')">✏️ Editar</div>
                            <div class="pf-menu-item pf-menu-del" onclick="deletePost(<?= $post['id'] ?>)">🗑️ Eliminar</div>
                        </div>
                    </div>
                    <div class="pf-content" id="content-<?= $post['id'] ?>"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
                    <?php if ($post['image']): ?>
                        <img src="<?= htmlspecialchars($post['image']) ?>" class="pf-post-img">
                    <?php endif; ?>
                    
                    <!-- Estadísticas de Likes y Comentarios -->
                    <div class="pf-stats">
                        <div class="pf-stat">👍 <?= $post['likes_count'] ?> Me gusta</div>
                        <div class="pf-stat">💬 <?= $post['comments_count'] ?> comentarios</div>
                    </div>
                    
                    <!-- Sección de Comentarios -->
                    <?php if (!empty($post['comments'])): ?>
                        <div class="pf-comments">
                            <h4 style="color:#3b82f6; margin:0 0 10px 0; font-size:14px;">💬 Comentarios</h4>
                            <?php foreach ($post['comments'] as $comment): 
                                $comment_avatar = !empty($comment['profile_pic']) ? $comment['profile_pic'] : 'default.png';
                            ?>
                                <div class="pf-comment">
                                    <img src="assets/uploads/avatars/<?= htmlspecialchars($comment_avatar) ?>" onerror="this.src='https://via.placeholder.com/32'">
                                    <div class="pf-comment-bubble">
                                        <div class="pf-comment-author">@<?= htmlspecialchars($comment['username']) ?></div>
                                        <div class="pf-comment-text"><?= htmlspecialchars($comment['content']) ?></div>
                                        <div class="pf-comment-time"><?= date('d/m H:i', strtotime($comment['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pf-no-comments">Sin comentarios aún</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('click', e => { if (!e.target.closest('.pf-dots')) document.querySelectorAll('.pf-menu').forEach(m => m.classList.remove('show')); });
        function toggleMenu(id) { document.querySelectorAll('.pf-menu').forEach(m => m.id !== 'menu-'+id && m.classList.remove('show')); document.getElementById('menu-'+id).classList.toggle('show'); }
        
        async function deletePost(id) {
            if (!confirm('¿Eliminar esta publicación?')) return;
            const res = await fetch('api/delete_post.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ post_id: id }) });
            const data = await res.json();
            if (data.success) document.querySelector(`.pf-post[data-id="${id}"]`).remove(); else alert(data.error);
        }

        async function editPost(id, content) {
            const div = document.getElementById('content-'+id);
            if (div.classList.contains('pf-editing')) {
                const val = div.querySelector('textarea').value.trim();
                if (!val) return;
                const res = await fetch('api/edit_post.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ post_id: id, content: val }) });
                const data = await res.json();
                if (data.success) { div.innerHTML = val.replace(/\n/g, '<br>'); div.classList.remove('pf-editing'); }
            } else {
                div.classList.add('pf-editing');
                div.innerHTML = `<textarea rows="3">${content}</textarea>`;
            }
            document.getElementById('menu-'+id).classList.remove('show');
        }
    </script>
</body>
</html>