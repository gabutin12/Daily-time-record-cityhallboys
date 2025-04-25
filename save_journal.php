<?php
session_start();
require_once "db_connect.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['id']) || !$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['id'];
$date = $data['date'];
$name = $data['name'];
$department = $data['department'];
$text = $data['text'];

$sql = "INSERT INTO journals (user_id, date, name, department, text) 
        VALUES (?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        name = VALUES(name),
        department = VALUES(department),
        text = VALUES(text)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $user_id, $date, $name, $department, $text);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
