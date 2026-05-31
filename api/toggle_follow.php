<?php
require '../config.php';
requireLogin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$target_id = (int)($data['user_id'] ?? 0);
if (!$target_id || $target_id == $_SESSION['user_id']) { echo json_encode(['success' => false, 'error' => 'ID inválido']); exit; }

try {
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $target_id]);
    
    if ($stmt->fetch()) {
        $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")->execute([$_SESSION['user_id'], $target_id]);
        $is_following = false;
    } else {
        $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $target_id]);
        $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type) VALUES (?, ?, 'follow')")
            ->execute([$target_id, $_SESSION['user_id']]);
        $is_following = true;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
    $stmt->execute([$target_id]);
    echo json_encode(['success' => true, 'following' => $is_following, 'count' => $stmt->fetch()['count']]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}