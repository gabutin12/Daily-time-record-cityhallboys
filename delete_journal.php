<?php
// filepath: c:\xampp\htdocs\DTR\delete_journal.php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing date']);
    exit;
}

$sql = "DELETE FROM journals WHERE user_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $_SESSION['id'], $data['date']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
