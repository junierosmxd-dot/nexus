<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json');

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$room_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.profile_pic, m.image
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.room_id = ?
        ORDER BY m.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$room_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($messages);
    
} catch (Exception $e) {
    error_log('Error en fetch_messages: ' . $e->getMessage());
    echo json_encode([]);
}