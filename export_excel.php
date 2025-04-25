<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    die('Not authorized');
}

// Add the calculation functions here
function formatDecimalToTime($decimal)
{
    // If input is already in HH:MM format, return as is
    if (is_string($decimal) && strpos($decimal, ':') !== false) {
        return $decimal;
    }

    // Convert to float if string number
    if (is_string($decimal)) {
        $decimal = floatval($decimal);
    }

    // Handle null or invalid input
    if ($decimal === null || !is_numeric($decimal)) {
        return '0:00';
    }

    $hours = floor($decimal);
    $minutes = round(($decimal - $hours) * 60);
    return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
}

function calculateTotalHours($user_id)
{
    global $conn;
    $sql = "SELECT SUM(total_hours) as total FROM dtr_records WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return formatDecimalToTime($result['total'] ?? 0);
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
    return formatDecimalToTime($totalHours);
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
    return formatDecimalToTime($totalHours);
}

// Get username from database
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];

// Set headers for Excel with username
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="DTR_Export_' . $username . '_' . date('Y-m-d') . '.xls"');

// Start HTML output
echo '
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .header {
            background: #4F81BD;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            font-size: 14pt;
        }
        .subheader {
            background: #DCE6F1;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000000;
            padding: 6px;
            font-size: 12pt;
        }
        td {
            border: 1px solid #000000;
            padding: 6px;
            mso-number-format:"\\@";
            font-size: 12pt;
            vertical-align: middle;
        }
        .saturday {
            background: #E6F3FF;
        }
        .total-section {
            background: #4F81BD;
            color: white;
            font-weight: bold;
            padding: 8px;
            font-size: 12pt;
        }
        .time-cell {
            text-align: center;
        }
        .total-row td {
            font-weight: bold;
            background: #F2F2F2;
        }
    </style>
</head>
<body>';

// Create table
echo '<table>
    <tr>
        <td class="header" colspan="7">Daily Time Record - ' . date('Y') . '</td>
    </tr>
    <tr><td colspan="7"></td></tr>
    <tr>
        <td class="subheader">Date</td>
        <td class="subheader">Time In (AM)</td>
        <td class="subheader">Time Out (AM)</td>
        <td class="subheader">Time In (PM)</td>
        <td class="subheader">Time Out (PM)</td>
        <td class="subheader">Daily Hours</td>
        <td class="subheader">Is Saturday</td>
    </tr>';

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
    $rowClass = $record['day_of_week'] == 7 ? ' class="saturday"' : '';
    echo "<tr$rowClass>
        <td>" . date('Y-m-d', strtotime($record['date'])) . "</td>
        <td class='time-cell'>" . ($record['time_in_am'] ? date('H:i', strtotime($record['time_in_am'])) : '') . "</td>
        <td class='time-cell'>" . ($record['time_out_am'] ? date('H:i', strtotime($record['time_out_am'])) : '') . "</td>
        <td class='time-cell'>" . ($record['time_in_pm'] ? date('H:i', strtotime($record['time_in_pm'])) : '') . "</td>
        <td class='time-cell'>" . ($record['time_out_pm'] ? date('g:i', strtotime($record['time_out_pm'])) : '') . "</td>
        <td class='time-cell'>" . formatDecimalToTime($record['total_hours']) . "</td>
        <td class='time-cell'>" . ($record['day_of_week'] == 7 ? 'Yes' : 'No') . "</td>
    </tr>";
}

// Add total hours summary
echo '
    <tr><td colspan="7"></td></tr>
    <tr><td colspan="7"></td></tr>
    <tr>
        <td class="total-section" colspan="7">Total Hours Summary</td>
    </tr>
    <tr class="total-row">
        <td colspan="2">Regular Hours:</td>
        <td colspan="5">' . formatDecimalToTime(calculateTotalHours($_SESSION['id'])) . '</td>
    </tr>
    <tr class="total-row">
        <td colspan="2">Hours with Saturday x2:</td>
        <td colspan="5">' . formatDecimalToTime(calculateTotalHoursWithSaturday($_SESSION['id'])) . '</td>
    </tr>
    <tr class="total-row">
        <td colspan="2">Hours Minus Lunch:</td>
        <td colspan="5">' . formatDecimalToTime(calculateTotalHoursMinusLunch($_SESSION['id'])) . '</td>
    </tr>
</table>
</body>
</html>';

exit;
