<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json');

$posts = $pdo->query("
    SELECT 
        p.id, p.content, p.image, p.created_at, p.user_id,
        u.username, u.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = {$_SESSION['user_id']}) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 50
")->fetchAll();

foreach ($posts as &$post) {
    // Obtener comentarios
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_pic 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post['id']]);
    $post['comments'] = $stmt->fetchAll();
}

echo json_encode($posts);