<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $user_id = $_SESSION['id'];

    // Delete all records for the current user
    $sql = "DELETE FROM dtr_records WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'All records have been reset'
        ]);
    } else {
        throw new Exception('Failed to reset records');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
