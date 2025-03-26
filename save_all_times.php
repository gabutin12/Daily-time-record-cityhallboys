<?php
session_start();
require_once 'db_connect.php';

// Prevent any output before our JSON response
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    $user_id = $_SESSION['id'];
    $date = $data['date'];
    $times = $data['times'];

    // Validate required data
    if (!$date || !$times) {
        throw new Exception('Missing required data');
    }

    // Start transaction
    $conn->begin_transaction();

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
            "ssssis",
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

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    // Commit transaction
    $conn->commit();

    // Calculate updated totals
    $sql = "SELECT 
            SUM(total_hours) as total,
            SUM(CASE WHEN DAYOFWEEK(date) = 7 THEN total_hours * 2 ELSE total_hours END) as total_with_saturday,
            SUM(CASE WHEN DAYOFWEEK(date) = 7 THEN (total_hours - 1) * 2 ELSE total_hours - 1 END) as total_minus_lunch
            FROM dtr_records WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $totals = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'message' => 'Times saved successfully',
        'total_hours' => $totals['total'] ?? 0,
        'total_hours_with_saturday' => $totals['total_with_saturday'] ?? 0,
        'total_hours_minus_lunch' => $totals['total_minus_lunch'] ?? 0
    ]);
} catch (Exception $e) {
    if (version_compare(mysqli_get_server_info($conn), '5.5', '>=')) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
