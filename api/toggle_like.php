<?php
require '../config.php';
requireLogin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);
if (!$post_id) { echo json_encode(['success' => false, 'error' => 'Post inválido']); exit; }

try {
    // Obtener dueño del post
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post_owner = $stmt->fetch()['user_id'];

    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $_SESSION['user_id']]);
        $liked = false;
    } else {
        $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $_SESSION['user_id']]);
        if ($post_owner != $_SESSION['user_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, post_id) VALUES (?, ?, 'like', ?)")
                ->execute([$post_owner, $_SESSION['user_id'], $post_id]);
        }
        $liked = true;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    echo json_encode(['success' => true, 'liked' => $liked, 'count' => $stmt->fetch()['count']]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}