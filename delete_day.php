<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get the data from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'] ?? null;

if (!$date) {
    echo json_encode(['success' => false, 'message' => 'No date provided']);
    exit;
}

// Delete the records for this date
$sql = "DELETE FROM dtr_records WHERE user_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $_SESSION['id'], $date);

if ($stmt->execute()) {
    // Calculate new totals
    $sql_total = "SELECT SUM(total_hours) as total FROM dtr_records WHERE user_id = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $_SESSION['id']);
    $stmt_total->execute();
    $result = $stmt_total->get_result()->fetch_assoc();
    
    // Calculate total hours with Saturday
    $sql_saturday = "SELECT date, total_hours FROM dtr_records WHERE user_id = ?";
    $stmt_saturday = $conn->prepare($sql_saturday);
    $stmt_saturday->bind_param("i", $_SESSION['id']);
    $stmt_saturday->execute();
    $result_saturday = $stmt_saturday->get_result();
    
    $total_with_saturday = 0;
    while ($row = $result_saturday->fetch_assoc()) {
        if (date('N', strtotime($row['date'])) == 6) { // 6 is Saturday
            $total_with_saturday += $row['total_hours'] * 2;
        } else {
            $total_with_saturday += $row['total_hours'];
        }
    }
    
    // Calculate total minus lunch
    $total_minus_lunch = max(0, ($result['total'] ?? 0) - (count_working_days($_SESSION['id']) * 1));
    
    echo json_encode([
        'success' => true,
        'total_hours' => $result['total'] ?? 0,
        'total_hours_with_saturday' => $total_with_saturday,
        'total_hours_minus_lunch' => $total_minus_lunch
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

function count_working_days($user_id) {
    global $conn;
    $sql = "SELECT COUNT(DISTINCT date) as days FROM dtr_records WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['days'] ?? 0;
}