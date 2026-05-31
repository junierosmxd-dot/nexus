<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Post inválido']);
    exit;
}

// Verificar que el post pertenece al usuario
$stmt = $pdo->prepare("SELECT id, image FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'No tienes permiso para eliminar este post']);
    exit;
}

// Eliminar imagen si existe
if ($post['image'] && file_exists('../' . $post['image'])) {
    unlink('../' . $post['image']);
}

// Eliminar post
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $_SESSION['user_id']]);

echo json_encode(['success' => true]);