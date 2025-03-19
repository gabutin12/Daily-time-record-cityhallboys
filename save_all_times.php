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
$times = $data['times'];

try {
    // Check if record exists
    $check_sql = "SELECT id FROM dtr_records WHERE user_id = ? AND date = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $user_id, $date);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing record
        $sql = "UPDATE dtr_records SET 
                time_in_am = ?, 
                time_out_am = ?, 
                time_in_pm = ?, 
                time_out_pm = ? 
                WHERE user_id = ? AND date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssss",
            $times['time_in_am'],
            $times['time_out_am'],
            $times['time_in_pm'],
            $times['time_out_pm'],
            $user_id,
            $date
        );
    } else {
        // Insert new record
        $sql = "INSERT INTO dtr_records (user_id, date, time_in_am, time_out_am, time_in_pm, time_out_pm) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssss",
            $user_id,
            $date,
            $times['time_in_am'],
            $times['time_out_am'],
            $times['time_in_pm'],
            $times['time_out_pm']
        );
    }

    if ($stmt->execute()) {
        // Fetch updated record
        $fetch_sql = "SELECT * FROM dtr_records WHERE user_id = ? AND date = ?";
        $fetch_stmt = $conn->prepare($fetch_sql);
        $fetch_stmt->bind_param("is", $user_id, $date);
        $fetch_stmt->execute();
        $updated_record = $fetch_stmt->get_result()->fetch_assoc();

        echo json_encode([
            'success' => true,
            'message' => 'Times saved successfully',
            'records' => $updated_record
        ]);
    } else {
        throw new Exception('Failed to save times');
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
