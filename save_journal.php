<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['date']) || !isset($data['department']) || !isset($data['text'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Check if entry exists
$sql = "INSERT INTO journals (user_id, date, department, text) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        department = VALUES(department), 
        text = VALUES(text)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isss",
    $_SESSION['id'],
    $data['date'],
    $data['department'],
    $data['text']
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
