<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all'; // 'users', 'posts', 'all'

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['users' => [], 'posts' => []]);
    exit;
}

$results = ['users' => [], 'posts' => []];

// Buscar usuarios
if ($type === 'all' || $type === 'users') {
    $stmt = $pdo->prepare("SELECT id, username, email, profile_pic, created_at 
                           FROM users 
                           WHERE username LIKE ? OR email LIKE ?
                           LIMIT 20");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $results['users'] = $stmt->fetchAll();
}

// Buscar posts
if ($type === 'all' || $type === 'posts') {
    $stmt = $pdo->prepare("SELECT p.id, p.content, p.image, p.created_at, 
                                  u.id as user_id, u.username, u.profile_pic
                           FROM posts p
                           JOIN users u ON p.user_id = u.id
                           WHERE p.content LIKE ?
                           ORDER BY p.created_at DESC
                           LIMIT 20");
    $stmt->execute(["%$query%"]);
    $results['posts'] = $stmt->fetchAll();
}

echo json_encode($results);