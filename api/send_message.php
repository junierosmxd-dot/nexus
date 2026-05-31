<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$room_id = (int)($_POST['room_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$image_path = null;

if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Sala inválida']);
    exit;
}

// Procesar imagen
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $_FILES['image']['name'];
    $temp_name = $_FILES['image']['tmp_name'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($extension, $allowed)) {
        $new_filename = uniqid('room_') . '.' . $extension;
        $upload_dir = '../assets/uploads/chats/rooms/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($temp_name, $destination)) {
            $image_path = 'assets/uploads/chats/rooms/' . $new_filename;
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar la imagen']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Formato no válido']);
        exit;
    }
}

if (empty($content) && empty($image_path)) {
    echo json_encode(['success' => false, 'error' => 'Escribe un mensaje o sube una imagen']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages (room_id, user_id, content, image, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$room_id, $_SESSION['user_id'], $content, $image_path]);
    
    // Notificar al creador de la sala
    $stmt = $pdo->prepare("SELECT created_by FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    if ($room && $room['created_by'] != $_SESSION['user_id']) {
        $notif_content = !empty($content) ? $content : '📷 Envió una imagen';
        $pdo->prepare("
            INSERT INTO notifications (user_id, sender_id, type, room_id, content, created_at) 
            VALUES (?, ?, 'room_message', ?, ?, NOW())
        ")->execute([$room['created_by'], $_SESSION['user_id'], $room_id, $notif_content]);
    }
    
    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId(), 'image' => $image_path]);
    
} catch (Exception $e) {
    error_log('Error en send_message: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno']);
}