<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)($data['post_id'] ?? 0);
$content = trim($data['content'] ?? '');

if (!$post_id || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

// Verificar que el post pertenece al usuario
$stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$content, $post_id, $_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'new_content' => $content]);
} else {
    echo json_encode(['success' => false, 'error' => 'No se pudo actualizar']);
}