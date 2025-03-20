<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_GET['date'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

$sql = "SELECT * FROM dtr_records WHERE user_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $_SESSION['id'], $_GET['date']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'records' => $result
]);
