<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    die('Not authorized');
}

// Add the calculation functions here
function calculateTotalHours($user_id)
{
    global $conn;
    $sql = "SELECT SUM(total_hours) as total FROM dtr_records WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return number_format($result['total'] ?? 0, 2);
}

function calculateTotalHoursWithSaturday($user_id)
{
    global $conn;
    $sql = "SELECT date, total_hours, DAYOFWEEK(date) as day_of_week 
            FROM dtr_records WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalHours = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['day_of_week'] == 7) {
            $totalHours += ($row['total_hours'] * 2);
        } else {
            $totalHours += $row['total_hours'];
        }
    }
    return number_format($totalHours, 2);
}

function calculateTotalHoursMinusLunch($user_id)
{
    global $conn;
    $sql = "SELECT date, total_hours, DAYOFWEEK(date) as day_of_week 
            FROM dtr_records WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalHours = 0;
    while ($row = $result->fetch_assoc()) {
        $hoursForDay = $row['total_hours'] - 1;
        if ($row['day_of_week'] == 7) {
            $hoursForDay = ($hoursForDay * 2);
        }
        $totalHours += $hoursForDay;
    }
    return number_format($totalHours, 2);
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="DTR_Export_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Add headers
fputcsv($output, ['Daily Time Record - ' . date('Y')]);
fputcsv($output, []); // Empty row
fputcsv($output, ['Date', 'Time In (AM)', 'Time Out (AM)', 'Time In (PM)', 'Time Out (PM)', 'Daily Hours', 'Is Saturday']);

// Get records
$sql = "SELECT 
        date,
        time_in_am,
        time_out_am,
        time_in_pm,
        time_out_pm,
        total_hours,
        DAYOFWEEK(date) as day_of_week
        FROM dtr_records 
        WHERE user_id = ? 
        ORDER BY date";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

while ($record = $result->fetch_assoc()) {
    fputcsv($output, [
        date('Y-m-d', strtotime($record['date'])),
        $record['time_in_am'] ? date('h:i A', strtotime($record['time_in_am'])) : '',
        $record['time_out_am'] ? date('h:i A', strtotime($record['time_out_am'])) : '',
        $record['time_in_pm'] ? date('h:i A', strtotime($record['time_in_pm'])) : '',
        $record['time_out_pm'] ? date('h:i A', strtotime($record['time_out_pm'])) : '',
        number_format($record['total_hours'], 2),
        $record['day_of_week'] == 7 ? 'Yes' : 'No'
    ]);
}

// Add total hours summary
fputcsv($output, []); // Empty row
fputcsv($output, []); // Empty row
fputcsv($output, ['Total Hours Summary']);
fputcsv($output, ['Regular Hours:', calculateTotalHours($_SESSION['id'])]);
fputcsv($output, ['Hours with Saturday x2:', calculateTotalHoursWithSaturday($_SESSION['id'])]);
fputcsv($output, ['Hours Minus Lunch:', calculateTotalHoursMinusLunch($_SESSION['id'])]);

fclose($output);
exit;
