<?php
require 'config.php';
requireLogin();
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

$search_query = trim($_GET['q'] ?? '');
$search_type = $_GET['type'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda - NexusChat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .search-box { background: #1f1f1f; padding: 30px; border-radius: 12px; border: 1px solid #333; margin-bottom: 30px; }
        .search-input-wrapper { display: flex; gap: 10px; margin-bottom: 15px; }
        .search-input-wrapper input { flex: 1; padding: 14px; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; color: #fff; }
        .search-input-wrapper button { padding: 14px 30px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .filters { display: flex; gap: 10px; }
        .filter-btn { padding: 8px 16px; background: #2a2a2a; border: 1px solid #444; border-radius: 6px; color: #fff; text-decoration: none; }
        .filter-btn.active { background: #3b82f6; border-color: #3b82f6; }
        .results-section { margin-bottom: 30px; }
        .results-section h2 { color: #3b82f6; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #333; }
        .result-item { background: #1f1f1f; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #333; }
        .result-item:hover { border-color: #3b82f6; }
        .user-result { display: flex; align-items: center; gap: 15px; text-decoration: none; color: inherit; }
        .user-result img { width: 50px; height: 50px; border-radius: 50%; }
        .user-info h3 { color: #3b82f6; margin-bottom: 5px; }
        .user-info p { color: #888; font-size: 14px; }
        .post-result .post-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .post-result .post-header img { width: 35px; height: 35px; border-radius: 50%; }
        .post-result .post-content { color: #e5e5e5; line-height: 1.5; margin: 10px 0; }
        .post-result .post-image { width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-top: 10px; }
        .post-result .post-time { color: #888; font-size: 12px; margin-top: 10px; }
        .no-results { text-align: center; color: #888; padding: 40px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container">
        <div class="search-box">
            <form method="GET" action="search.php">
                <div class="search-input-wrapper">
                    <input type="text" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($search_query) ?>" required>
                    <button type="submit">🔍 Buscar</button>
                </div>
            </form>
            <div class="filters">
                <a href="?q=<?= urlencode($search_query) ?>&type=all" class="filter-btn <?= $search_type === 'all' ? 'active' : '' ?>">Todo</a>
                <a href="?q=<?= urlencode($search_query) ?>&type=users" class="filter-btn <?= $search_type === 'users' ? 'active' : '' ?>">Usuarios</a>
                <a href="?q=<?= urlencode($search_query) ?>&type=posts" class="filter-btn <?= $search_type === 'posts' ? 'active' : '' ?>">Publicaciones</a>
            </div>
        </div>

        <?php if (!empty($search_query)): 
            $users = []; $posts = [];
            if ($search_type === 'all' || $search_type === 'users') {
                $stmt = $pdo->prepare("SELECT id, username, email, profile_pic FROM users WHERE username LIKE ? OR email LIKE ? LIMIT 20");
                $stmt->execute(["%$search_query%", "%$search_query%"]); $users = $stmt->fetchAll();
            }
            if ($search_type === 'all' || $search_type === 'posts') {
                $stmt = $pdo->prepare("SELECT p.*, u.username, u.profile_pic FROM posts p JOIN users u ON p.user_id = u.id WHERE p.content LIKE ? ORDER BY p.created_at DESC LIMIT 20");
                $stmt->execute(["%$search_query%"]); $posts = $stmt->fetchAll();
            }
        ?>
            <?php if ($search_type === 'all' || $search_type === 'users'): ?>
                <div class="results-section">
                    <h2>👥 Usuarios (<?= count($users) ?>)</h2>
                    <?php if (empty($users)): ?><div class="no-results">No hay usuarios</div>
                    <?php else: foreach ($users as $user): ?>
                        <div class="result-item">
                            <a href="public_profile.php?id=<?= $user['id'] ?>" class="user-result">
                                <img src="assets/uploads/avatars/<?= htmlspecialchars($user['profile_pic'] ?? 'default.png') ?>" onerror="this.src='https://via.placeholder.com/50'">
                                <div class="user-info"><h3>@<?= htmlspecialchars($user['username']) ?></h3><p><?= htmlspecialchars($user['email']) ?></p></div>
                            </a>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($search_type === 'all' || $search_type === 'posts'): ?>
                <div class="results-section">
                    <h2>📝 Publicaciones (<?= count($posts) ?>)</h2>
                    <?php if (empty($posts)): ?><div class="no-results">No hay publicaciones</div>
                    <?php else: foreach ($posts as $post): ?>
                        <div class="result-item post-result">
                            <div class="post-header">
                                <img src="assets/uploads/avatars/<?= htmlspecialchars($post['profile_pic'] ?? 'default.png') ?>" onerror="this.src='https://via.placeholder.com/35'">
                                <strong style="color: #3b82f6;">@<?= htmlspecialchars($post['username']) ?></strong>
                            </div>
                            <div class="post-content"><?= htmlspecialchars($post['content']) ?></div>
                            <?php if ($post['image']): ?><img src="<?= htmlspecialchars($post['image']) ?>" class="post-image"><?php endif; ?>
                            <div class="post-time"><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($users) && empty($posts)): ?><div class="no-results">🔍 Sin resultados para "<?= htmlspecialchars($search_query) ?>"</div><?php endif; ?>
        <?php else: ?>
            <div class="no-results">💡 Escribe para buscar</div>
        <?php endif; ?>
    </div>
</body>
</html>