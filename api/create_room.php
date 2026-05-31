<?php
require '../config.php';
requireLogin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$desc = trim($data['description'] ?? '');

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Nombre requerido']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO rooms (name, description, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$name, $desc, $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al crear']);
}