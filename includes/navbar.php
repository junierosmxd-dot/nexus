<?php
// Lógica interna
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

$page = basename($_SERVER['PHP_SELF']);
$hide = in_array($page, ['private_chat.php', 'chat.php']);
?>
<style>
.nc-bar { background: #111; border-bottom: 1px solid #333; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; box-sizing: border-box; width: 100%; }
.nc-left, .nc-right { display: flex; align-items: center; gap: 12px; }
.nc-center { flex: 1; max-width: 450px; margin: 0 20px; }
.nc-logo { font-size: 18px; font-weight: 700; color: #3b82f6; text-decoration: none; }
.nc-btn { background: transparent; border: none; color: #888; font-size: 20px; cursor: pointer; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative; text-decoration: none; }
.nc-btn:hover { background: #2a2a2a; color: #fff; }
.nc-badge { position: absolute; top: 0; right: 0; background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.nc-search { display: flex; width: 100%; }
.nc-input { flex: 1; background: #1f1f1f; border: 1px solid #333; border-right: none; border-radius: 20px 0 0 20px; padding: 8px 16px; color: #fff; font-size: 14px; outline: none; }
.nc-input:focus { border-color: #3b82f6; }
.nc-sbtn { background: #3b82f6; border: 1px solid #3b82f6; border-radius: 0 20px 20px 0; padding: 0 16px; color: #fff; cursor: pointer; display: flex; align-items: center; }
.nc-link { color: #fff; text-decoration: none; padding: 8px 14px; background: #1f1f1f; border-radius: 6px; font-size: 14px; font-weight: 500; transition: 0.2s; }
.nc-link:hover { background: #3b82f6; }
.nc-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid #3b82f6; }
</style>

<nav class="nc-bar">
    <div class="nc-left">
        <a href="index.php" class="nc-logo">NexusChat</a>
        <?php if (!$hide): ?>
        <a href="notifications.php" class="nc-btn">🔔<?php if ($unread_count > 0): ?><span class="nc-badge"><?= $unread_count ?></span><?php endif; ?></a>
        <?php endif; ?>
    </div>

    <?php if (!$hide): ?>
    <div class="nc-center">
        <form action="search.php" method="GET" class="nc-search">
            <input type="text" name="q" class="nc-input" placeholder="Buscar en NexusChat..." value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
            <button type="submit" class="nc-sbtn">🔍</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="nc-right">
        <a href="index.php" class="nc-link">Inicio</a>
        <a href="rooms.php" class="nc-link">Salas</a>
        <a href="profile.php" class="nc-link">Perfil</a>
        <a href="logout.php" class="nc-link" style="color:#ef4444;">Salir</a>
        <img src="assets/uploads/avatars/<?= htmlspecialchars($current_user['profile_pic'] ?? 'default.png') ?>" class="nc-avatar" onerror="this.src='https://via.placeholder.com/36'">
    </div>
</nav>