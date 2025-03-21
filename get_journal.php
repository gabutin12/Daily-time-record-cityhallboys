<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request']);
    exit;
}

$sql = "SELECT * FROM journals WHERE user_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $_SESSION['id'], $_GET['date']);
$stmt->execute();
$result = $stmt->get_result();
$journal = $result->fetch_assoc();

if ($journal) {
    echo json_encode([
        'success' => true,
        'journal' => [
            'department' => $journal['department'],
            'text' => $journal['text']
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'journal' => null
    ]);
}
