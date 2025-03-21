<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    die(json_encode(['error' => 'Not authorized']));
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

$sql = "SELECT 
        COUNT(DISTINCT date) as days_worked,
        AVG(total_hours) as avg_hours,
        SUM(CASE WHEN DAYOFWEEK(date) = 7 THEN 1 ELSE 0 END) as saturdays
        FROM dtr_records 
        WHERE user_id = ? 
        AND YEAR(date) = ? 
        AND MONTH(date) = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $_SESSION['id'], $year, $month);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'daysWorked' => $result['days_worked'],
    'averageHours' => round($result['avg_hours'], 2),
    'saturdaysWorked' => $result['saturdays']
]);
