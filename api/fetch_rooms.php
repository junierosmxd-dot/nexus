<?php
require '../config.php';
requireLogin();
header('Content-Type: application/json');
echo json_encode($pdo->query("SELECT id, name FROM rooms ORDER BY created_at DESC")->fetchAll());