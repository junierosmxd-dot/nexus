<?php
require '../config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$image_path = null;

// Debug
error_log("Receiver ID: " . $receiver_id);
error_log("Content: " . $content);
error_log("Files: " . print_r($_FILES, true));

if (!$receiver_id) {
    echo json_encode(['success' => false, 'error' => 'Sin receiver_id']);
    exit;
}

// Procesar imagen
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_name = uniqid('pvt_') . '.' . $ext;
        $upload_dir = '../assets/uploads/chats/private/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
            $image_path = 'assets/uploads/chats/private/' . $new_name;
        }
    }
}

if (empty($content) && empty($image_path)) {
    echo json_encode(['success' => false, 'error' => 'Mensaje vacío']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, content, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $receiver_id, $content, $image_path]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}