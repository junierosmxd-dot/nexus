<?php
require 'config.php';
requireLogin();
$stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);

$stmt = $pdo->prepare("SELECT n.*, u.username as sender_name, u.profile_pic as sender_pic, p.content as post_preview, r.name as room_name FROM notifications n JOIN users u ON n.sender_id = u.id LEFT JOIN posts p ON n.post_id = p.id LEFT JOIN rooms r ON n.room_id = r.id WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - NexusChat</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a0a; color: #fff; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .notif { background: #1f1f1f; border: 1px solid #333; border-radius: 8px; padding: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; }
        .notif img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .notif-text { flex: 1; }
        .notif-text strong { color: #3b82f6; }
        .notif-text span { color: #888; font-size: 13px; }
        .notif-text a { color: #60a5fa; text-decoration: none; }
        .notif-icon { font-size: 24px; }
        .empty { text-align: center; color: #666; padding: 40px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="container">
        <h2 style="color:#3b82f6; margin: 20px 0;">🔔 Notificaciones</h2>
        <?php if (empty($notifications)): ?>
            <div class="empty">No tienes notificaciones.</div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notif">
                    <img src="assets/uploads/avatars/<?= htmlspecialchars($n['sender_pic']) ?>" onerror="this.src='https://via.placeholder.com/45'">
                    <div class="notif-text">
                        <?php if ($n['type'] === 'like'): ?>
                            <strong>@<?= htmlspecialchars($n['sender_name']) ?></strong> le dio ❤️ a tu publicación.
                        <?php elseif ($n['type'] === 'comment'): ?>
                            <strong>@<?= htmlspecialchars($n['sender_name']) ?></strong> comentó: "<em><?= htmlspecialchars(substr($n['content'], 0, 50)) ?>...</em>"
                        <?php elseif ($n['type'] === 'follow'): ?>
                            <strong>@<?= htmlspecialchars($n['sender_name']) ?></strong> comenzó a seguirte. 👤
                        <?php elseif ($n['type'] === 'private_message'): ?>
                            <strong>@<?= htmlspecialchars($n['sender_name']) ?></strong> te envió un mensaje: "<em><?= htmlspecialchars($n['content']) ?>...</em>"
                            <br><a href="private_chat.php?id=<?= $n['sender_id'] ?>">Ver →</a>
                        <?php elseif ($n['type'] === 'room_message'): ?>
                            <strong>@<?= htmlspecialchars($n['sender_name']) ?></strong> escribió en <strong><?= htmlspecialchars($n['room_name']) ?></strong>: "<em><?= htmlspecialchars($n['content']) ?>...</em>"
                            <br><a href="chat.php?id=<?= $n['room_id'] ?>">Ver →</a>
                        <?php endif; ?>
                        <br><span><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></span>
                    </div>
                    <div class="notif-icon"><?= match($n['type']) { 'like' => '❤️', 'comment' => '💬', 'follow' => '👤', 'private_message' => '💬', default => '🏫' } ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>