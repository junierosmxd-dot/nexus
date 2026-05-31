<?php
require 'config.php';
requireLogin();
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

$target_id = (int)($_GET['id'] ?? 0);
if ($target_id == $_SESSION['user_id']) { header('Location: profile.php'); exit; }

$stmt = $pdo->prepare("SELECT id, username, email, profile_pic FROM users WHERE id = ?");
$stmt->execute([$target_id]);
$target = $stmt->fetch();
if (!$target) die('<div style="text-align:center; padding:50px; color:#fff;"><h2>🚫 Usuario no encontrado</h2><a href="index.php" style="color:#3b82f6;">Volver</a></div>');

$stmt = $pdo->prepare("SELECT COUNT(*) as c FROM follows WHERE following_id = ?"); $stmt->execute([$target_id]); $followers = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT COUNT(*) as c FROM follows WHERE follower_id = ?"); $stmt->execute([$target_id]); $following = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT COUNT(*) as c FROM posts WHERE user_id = ?"); $stmt->execute([$target_id]); $posts_count = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$_SESSION['user_id'], $target_id]);
$is_following = (bool)$stmt->fetch();

$posts_stmt = $pdo->prepare("SELECT p.*, u.username, u.profile_pic, (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count, (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked, (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count FROM posts p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
$posts_stmt->execute([$_SESSION['user_id'], $target_id]);
$posts = $posts_stmt->fetchAll();

$target_avatar = !empty($target['profile_pic']) ? $target['profile_pic'] : 'default.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@<?= htmlspecialchars($target['username']) ?> - NexusChat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        .profile-header { background: #1f1f1f; border: 1px solid #333; border-radius: 12px; padding: 30px; text-align: center; margin-bottom: 25px; }
        .avatar-large { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #3b82f6; margin-bottom: 15px; }
        .username { font-size: 24px; font-weight: bold; }
        .handle { color: #888; margin-bottom: 20px; }
        .stats { display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; }
        .stat { text-align: center; }
        .stat-num { font-weight: bold; font-size: 18px; }
        .stat-label { font-size: 13px; color: #888; }
        .actions { display: flex; justify-content: center; gap: 10px; }
        .btn { padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; }
        .btn-follow { background: #3b82f6; color: #fff; }
        .btn-follow.following { background: #2a2a2a; color: #fff; border: 1px solid #444; }
        .btn-msg { background: #2a2a2a; color: #fff; border: 1px solid #444; }
        .post { background: #1f1f1f; border: 1px solid #333; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .post-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .post-header img { width: 35px; height: 35px; border-radius: 50%; }
        .post-author { font-weight: 600; }
        .post-time { font-size: 12px; color: #888; }
        .post-content { line-height: 1.5; margin: 10px 0; }
        .post img { width: 100%; border-radius: 8px; margin-top: 10px; }
        .post-stats { padding: 10px 0; border-top: 1px solid #333; margin-top: 10px; display: flex; justify-content: space-between; color: #888; font-size: 13px; }
        .post-actions { display: flex; padding: 8px 0; }
        .action-btn { flex: 1; padding: 8px; border: none; background: transparent; color: #888; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .action-btn:hover { background: #2a2a2a; border-radius: 6px; }
        .action-btn.liked { color: #3b82f6; }
        .comment-input { width: 100%; padding: 8px; background: #2a2a2a; border: 1px solid #444; border-radius: 20px; color: #fff; outline: none; }
        .loading { text-align: center; padding: 30px; color: #666; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container">
        <div class="profile-header">
            <img src="assets/uploads/avatars/<?= htmlspecialchars($target_avatar) ?>" class="avatar-large" onerror="this.src='https://via.placeholder.com/120'">
            <div class="username"><?= htmlspecialchars($target['username']) ?></div>
            <div class="handle">@<?= htmlspecialchars($target['username']) ?> · <?= htmlspecialchars($target['email']) ?></div>
            <div class="stats">
                <div class="stat"><div class="stat-num" id="followersCount"><?= $followers ?></div><div class="stat-label">Seguidores</div></div>
                <div class="stat"><div class="stat-num"><?= $following ?></div><div class="stat-label">Siguiendo</div></div>
                <div class="stat"><div class="stat-num"><?= $posts_count ?></div><div class="stat-label">Publicaciones</div></div>
            </div>
            <div class="actions">
                <button class="btn btn-follow <?= $is_following ? 'following' : '' ?>" id="followBtn" onclick="toggleFollow()"><?= $is_following ? '✓ Siguiendo' : ' Seguir' ?></button>
                <a href="private_chat.php?id=<?= $target_id ?>" class="btn btn-msg">💬 Mensaje</a>
            </div>
        </div>

        <h2 style="color:#3b82f6; margin-bottom:20px;">📝 Publicaciones</h2>
        <?php if (empty($posts)): ?>
            <div class="loading">No ha publicado nada.</div>
        <?php else: ?>
            <?php foreach ($posts as $post): 
                $post_avatar = !empty($post['profile_pic']) ? $post['profile_pic'] : 'default.png';
            ?>
                <div class="post" data-id="<?= $post['id'] ?>">
                    <div class="post-header">
                        <img src="assets/uploads/avatars/<?= htmlspecialchars($post_avatar) ?>" onerror="this.src='https://via.placeholder.com/35'">
                        <div><div class="post-author">@<?= htmlspecialchars($post['username']) ?></div><div class="post-time"><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div></div>
                    </div>
                    <div class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
                    <?php if ($post['image']): ?><img src="<?= htmlspecialchars($post['image']) ?>"><?php endif; ?>
                    <div class="post-stats">
                        <span id="likes-<?= $post['id'] ?>">👍 <?= $post['likes_count'] ?> Me gusta</span>
                        <span>💬 <?= $post['comments_count'] ?> comentarios</span>
                    </div>
                    <div class="post-actions">
                        <button class="action-btn <?= $post['user_liked'] ? 'liked' : '' ?>" onclick="toggleLike(<?= $post['id'] ?>)">👍 Me gusta</button>
                        <button class="action-btn" onclick="document.getElementById('cmt-<?= $post['id'] ?>').focus()">💬 Comentar</button>
                    </div>
                    <div style="background:#151515; padding:10px; border-radius:6px; margin-top:5px;">
                        <input type="text" id="cmt-<?= $post['id'] ?>" class="comment-input" placeholder="Escribe un comentario..." onkeypress="if(event.key==='Enter') addComment(<?= $post['id'] ?>, this.value)">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        async function toggleFollow() {
            const btn = document.getElementById('followBtn'), countEl = document.getElementById('followersCount');
            const res = await fetch('api/toggle_follow.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ user_id: <?= $target_id ?> }) });
            const data = await res.json();
            if (data.success) { btn.classList.toggle('following'); btn.innerHTML = data.following ? '✓ Siguiendo' : '👤 Seguir'; countEl.textContent = data.count; }
        }
        async function toggleLike(postId) {
            const res = await fetch('api/toggle_like.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ post_id: postId }) });
            const data = await res.json();
            if (data.success) { document.querySelector(`.post[data-id="${postId}"] .action-btn`).classList.toggle('liked'); document.getElementById(`likes-${postId}`).textContent = `👍 ${data.count} Me gusta`; }
        }
        async function addComment(postId, content) {
            if (!content.trim()) return;
            const res = await fetch('api/add_comment.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ post_id: postId, content }) });
            const data = await res.json();
            if (data.success) { document.getElementById(`cmt-${postId}`).value = ''; location.reload(); }
        }
    </script>
</body>
</html>