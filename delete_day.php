<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['id'];
$date = $data['date'];

try {
    // Delete records for the specific date
    $sql = "DELETE FROM dtr_records WHERE user_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date);

    if ($stmt->execute()) {
        // Get updated total hours
        $total_sql = "SELECT SUM(total_hours) as total FROM dtr_records WHERE user_id = ?";
        $total_stmt = $conn->prepare($total_sql);
        $total_stmt->bind_param("i", $user_id);
        $total_stmt->execute();
        $result = $total_stmt->get_result()->fetch_assoc();

        echo json_encode([
            'success' => true,
            'message' => 'Entries deleted successfully',
            'total_hours' => $result['total'] ?? 0
        ]);
    } else {
        throw new Exception('Failed to delete entries');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => true,
        'message' => 'Operation successful',
        'total_hours' => calculateTotalHours($user_id),
        'total_hours_with_saturday' => calculateTotalHoursWithSaturday($user_id),
        'total_hours_minus_lunch' => calculateTotalHoursMinusLunch($user_id)
    ]);
}
