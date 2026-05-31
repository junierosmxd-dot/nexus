<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no válido']);
    exit;
}

$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$image_path = null;

// Procesar imagen
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_name = uniqid('post_') . '.' . $ext;
        $upload_dir = '../assets/uploads/posts/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $target = $upload_dir . $new_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = 'assets/uploads/posts/' . $new_name;
        }
    }
}

if (empty($content) && empty($image_path)) {
    echo json_encode(['success' => false, 'error' => 'Escribe algo o sube una imagen']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $content, $image_path]);
    
    echo json_encode(['success' => true, 'post_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}