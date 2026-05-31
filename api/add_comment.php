<?php
require '../config.php';
requireLogin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);
$content = trim($data['content'] ?? '');
if (!$post_id || empty($content)) { echo json_encode(['success' => false, 'error' => 'Datos inválidos']); exit; }

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
    $comment_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post_owner = $stmt->fetch()['user_id'];
    
    if ($post_owner != $_SESSION['user_id']) {
        $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, post_id, content) VALUES (?, ?, 'comment', ?, ?)")
            ->execute([$post_owner, $_SESSION['user_id'], $post_id, $content]);
    }
    $pdo->commit();

    $stmt = $pdo->prepare("SELECT c.*, u.username, u.profile_pic FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$comment_id]);
    echo json_encode(['success' => true, 'comment' => $stmt->fetch()]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}