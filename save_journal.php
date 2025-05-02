<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['id']) || !$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['id'];
$date = $data['date'];
$name = $data['name'];
$hte_name = $data['hte_name'];  // Changed from hteName to hte_name
$department = $data['department'];
$text = $data['text'];

// Check if entry exists
$check_sql = "SELECT id FROM journals WHERE user_id = ? AND date = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $user_id, $date);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing entry
    $sql = "UPDATE journals 
            SET name = ?, hte_name = ?, department = ?, text = ? 
            WHERE user_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $hte_name, $department, $text, $user_id, $date);
} else {
    // Insert new entry
    $sql = "INSERT INTO journals (user_id, date, name, hte_name, department, text) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $date, $name, $hte_name, $department, $text);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$check_stmt->close();
$stmt->close();
$conn->close();
