<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json'); // Add this line

if (!isset($_SESSION['id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['id'];
$date = $_GET['date'];

try {
    $sql = "SELECT * FROM journals WHERE user_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'journal' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No journal entry found', 'isNew' => true]);
    }
} catch (Exception $e) {
    echo json_encode(value: ['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
