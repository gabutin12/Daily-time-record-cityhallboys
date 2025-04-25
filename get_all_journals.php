<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sql = "SELECT j.*, d.time_in_am, d.time_out_am, d.time_in_pm, d.time_out_pm 
        FROM journals j 
        LEFT JOIN dtr_records d ON j.user_id = d.user_id AND j.date = d.date 
        WHERE j.user_id = ? 
        ORDER BY j.date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

$journals = [];
while ($row = $result->fetch_assoc()) {
    $journals[] = [
        'date' => $row['date'],
        'department' => $row['department'],
        'text' => $row['text'],
        'name' => $row['name'], // Added name field
        'time_in_am' => $row['time_in_am'] ? date('h:i A', strtotime($row['time_in_am'])) : '',
        'time_out_am' => $row['time_out_am'] ? date('h:i A', strtotime($row['time_out_am'])) : '',
        'time_in_pm' => $row['time_in_pm'] ? date('h:i A', strtotime($row['time_in_pm'])) : '',
        'time_out_pm' => $row['time_out_pm'] ? date('h:i A', strtotime($row['time_out_pm'])) : ''
    ];
}

echo json_encode([
    'success' => true,
    'journals' => $journals
]);
