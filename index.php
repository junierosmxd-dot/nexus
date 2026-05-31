<?php
require 'config.php';
requireLogin();
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusChat - Inicio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 650px; margin: 30px auto; padding: 0 20px; }
        .create-box { background: #1f1f1f; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .create-top { display: flex; gap: 12px; margin-bottom: 15px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .create-input { flex: 1; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; padding: 10px; color: #fff; resize: none; outline: none; }
        .create-actions { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #333; padding-top: 12px; }
        .upload-label { color: #60a5fa; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 6px; }
        .publish-btn { background: #3b82f6; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .preview-area { margin-top: 12px; display: none; text-align: center; background: #151515; padding: 8px; border-radius: 8px; }
        .preview-area img { max-height: 150px; border-radius: 6px; }
        .post { background: #1f1f1f; border: 1px solid #333; border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
        .post-header { padding: 15px; display: flex; align-items: center; gap: 12px; }
        .post-author { font-weight: 600; }
        .post-time { font-size: 12px; color: #888; }
        .post-content { padding: 0 15px 12px; line-height: 1.5; }
        .post-image { width: 100%; max-height: 500px; object-fit: cover; }
        .post-stats { padding: 12px 15px; border-top: 1px solid #333; border-bottom: 1px solid #333; display: flex; justify-content: space-between; color: #888; font-size: 13px; }
        .post-actions { display: flex; padding: 8px; }
        .action-btn { flex: 1; padding: 10px; border: none; background: transparent; color: #888; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .action-btn:hover { background: #2a2a2a; border-radius: 6px; }
        .action-btn.liked { color: #3b82f6; }
        .comments-section { padding: 12px 15px; background: #151515; }
        .comment { display: flex; gap: 10px; margin-bottom: 10px; }
        .comment-bubble { background: #2a2a2a; padding: 8px 12px; border-radius: 12px; }
        .comment-author { font-weight: 600; font-size: 13px; color: #3b82f6; }
        .comment-input { width: 100%; padding: 8px; background: #2a2a2a; border: 1px solid #444; border-radius: 20px; color: #fff; outline: none; }
        .loading { text-align: center; padding: 30px; color: #666; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container">
        <div class="create-box">
            <div class="create-top">
                <img src="assets/uploads/avatars/<?= htmlspecialchars($current_user['profile_pic'] ?? 'default.png') ?>" class="avatar" onerror="this.src='https://via.placeholder.com/40'">
                <textarea id="postContent" class="create-input" placeholder="¿Qué estás pensando?" rows="2"></textarea>
            </div>
            <input type="file" id="postImage" accept="image/*" style="display: none;">
            <div class="preview-area" id="previewArea">
                <img id="previewImg" src="" alt="">
                <div id="fileName" style="font-size:12px; color:#888; margin-top:6px;"></div>
            </div>
            <div class="create-actions">
                <label for="postImage" class="upload-label">📷 Foto</label>
                <button class="publish-btn" id="publishBtn">Publicar</button>
            </div>
        </div>
        <div id="feed"><div class="loading">Cargando...</div></div>
    </div>

    <script>
        const imageInput = document.getElementById('postImage');
        const previewArea = document.getElementById('previewArea');
        const previewImg = document.getElementById('previewImg');
        const fileName = document.getElementById('fileName');

        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    previewImg.src = e.target.result;
                    previewArea.style.display = 'block';
                    fileName.textContent = this.files[0].name;
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                previewArea.style.display = 'none';
                fileName.textContent = '';
            }
        });

        document.getElementById('publishBtn').addEventListener('click', async () => {
            const content = document.getElementById('postContent').value.trim();
            const file = imageInput.files[0];
            if (!content && !file) { alert('Escribe algo o sube una imagen'); return; }

            const formData = new FormData();
            formData.append('content', content);
            if (file) formData.append('image', file);

            const res = await fetch('api/create_post.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                document.getElementById('postContent').value = '';
                imageInput.value = '';
                previewArea.style.display = 'none';
                loadPosts();
            } else {
                alert('Error: ' + data.error);
            }
        });

        async function loadPosts() {
            const res = await fetch('api/fetch_posts.php');
            const posts = await res.json();
            const feed = document.getElementById('feed');
            if (posts.length === 0) { feed.innerHTML = '<div class="loading">No hay publicaciones</div>'; return; }

            feed.innerHTML = posts.map(post => `
                <div class="post" data-id="${post.id}">
                    <div class="post-header">
                        <img src="assets/uploads/avatars/${post.profile_pic}" class="avatar" onerror="this.src='https://via.placeholder.com/40'">
                        <div><div class="post-author">@${escapeHtml(post.username)}</div><div class="post-time">${formatDate(post.created_at)}</div></div>
                    </div>
                    ${post.content ? `<div class="post-content">${escapeHtml(post.content)}</div>` : ''}
                    ${post.image ? `<img src="${post.image}" class="post-image" loading="lazy">` : ''}
                    <div class="post-stats">
                        <span>👍 ${post.likes_count} Me gusta</span>
                        <span>💬 ${post.comments_count} comentarios</span>
                    </div>
                    <div class="post-actions">
                        <button class="action-btn ${post.user_liked ? 'liked' : ''}" onclick="toggleLike(${post.id})">👍 Me gusta</button>
                        <button class="action-btn" onclick="document.getElementById('cmt-${post.id}').focus()">💬 Comentar</button>
                    </div>
                    <div class="comments-section">
                        ${post.comments.map(c => `
                            <div class="comment">
                                <img src="assets/uploads/avatars/${c.profile_pic}" class="avatar" style="width:30px;height:30px;" onerror="this.src='https://via.placeholder.com/30'">
                                <div class="comment-bubble"><div class="comment-author">@${escapeHtml(c.username)}</div><div>${escapeHtml(c.content)}</div></div>
                            </div>
                        `).join('')}
                        <input type="text" id="cmt-${post.id}" class="comment-input" placeholder="Escribe un comentario..." onkeypress="if(event.key==='Enter') addComment(${post.id}, this.value)">
                    </div>
                </div>
            `).join('');
        }

        async function toggleLike(postId) {
            const res = await fetch('api/toggle_like.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ post_id: postId }) });
            const data = await res.json();
            if (data.success) loadPosts();
        }

        async function addComment(postId, content) {
            if (!content.trim()) return;
            const res = await fetch('api/add_comment.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ post_id: postId, content }) });
            const data = await res.json();
            if (data.success) loadPosts();
        }

        function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        function formatDate(dateStr) { const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000); if (diff < 60) return 'Ahora'; if (diff < 3600) return `Hace ${Math.floor(diff/60)} min`; if (diff < 86400) return `Hace ${Math.floor(diff/3600)} h`; return new Date(dateStr).toLocaleDateString(); }

        loadPosts();
        setInterval(loadPosts, 30000);
    </script>
</body>
</html>