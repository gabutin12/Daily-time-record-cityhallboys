<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    die(json_encode(['error' => 'Not authorized']));
}

function formatDecimalToTime($decimal)
{
    $hours = floor($decimal);
    $minutes = round(($decimal - $hours) * 60);
    return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$months = [];

// Get all records for the year
$sql = "SELECT DISTINCT 
        MONTH(date) as month,
        date, 
        time_in_am, 
        time_out_am, 
        time_in_pm, 
        time_out_pm, 
        total_hours,
        DAYOFWEEK(date) as day_of_week
        FROM dtr_records 
        WHERE user_id = ? AND YEAR(date) = ?
        ORDER BY date";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $_SESSION['id'], $year);
$stmt->execute();
$result = $stmt->get_result();
$records = [];

// Group records by month
while ($row = $result->fetch_assoc()) {
    $month = (int)$row['month'];
    if (!isset($records[$month])) {
        $records[$month] = [];
    }
    $records[$month][] = $row;
}

// Add after calculating individual month totals
$annualTotals = [
    'total_hours' => 0,
    'total_with_saturday' => 0,
    'total_minus_lunch' => 0,
    'total_days' => 0,
    'total_saturdays' => 0
];

// Only process months that have records
foreach ($records as $month => $monthRecords) {
    $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));

    // Calculate monthly totals
    $totalHours = 0;
    $totalHoursWithSat = 0;
    $totalHoursMinusLunch = 0;
    $daysWorked = count($monthRecords);
    $saturdays = 0;

    foreach ($monthRecords as $record) {
        $hours = $record['total_hours'];
        $isSaturday = ($record['day_of_week'] == 7);

        $totalHours += $hours;
        $totalHoursWithSat += $isSaturday ? ($hours * 2) : $hours;
        $totalHoursMinusLunch += ($hours - 1);
        if ($isSaturday) $saturdays++;
    }

    // Add to annual totals
    $annualTotals['total_hours'] += $totalHours;
    $annualTotals['total_with_saturday'] += $totalHoursWithSat;
    $annualTotals['total_minus_lunch'] += $totalHoursMinusLunch;
    $annualTotals['total_days'] += $daysWorked;
    $annualTotals['total_saturdays'] += $saturdays;

    // Generate calendar for this month
    $firstDay = date('w', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));

    // Update the month header style
    $calendar = "<div class='month-section'>
                <h3 class='month-header' style='color: black !important; padding: 10px; margin-bottom: 20px; border-radius: 5px; border: 2px solid black; -webkit-print-color-adjust: exact; print-color-adjust: exact;'>{$monthName}</h3>
                <table class='calendar'>
                <thead><tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                </tr></thead><tbody><tr>";

    // Add blank days
    for ($i = 0; $i < $firstDay; $i++) {
        $calendar .= "<td></td>";
    }

    // Fill in the days
    $dayOfWeek = $firstDay;
    for ($day = 1; $day <= $daysInMonth; $day++) {
        if ($dayOfWeek == 7) {
            $calendar .= "</tr><tr>";
            $dayOfWeek = 0;
        }

        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $dayRecords = array_filter($monthRecords, function ($r) use ($date) {
            return substr($r['date'], 0, 10) === $date;
        });

        $calendar .= "<td>
            <div class='day-number'>$day</div>";

        // Update the calendar entry part for time entries
        foreach ($dayRecords as $record) {
            if ($record['time_in_am'])
                $calendar .= "<div class='calendar-entry'><strong>" . date('h:i A', strtotime($record['time_in_am'])) . "</strong></div>";
            if ($record['time_out_am'])
                $calendar .= "<div class='calendar-entry'><strong>" . date('h:i A', strtotime($record['time_out_am'])) . "</strong></div>";
            if ($record['time_in_pm'])
                $calendar .= "<div class='calendar-entry'><strong>" . date('h:i A', strtotime($record['time_in_pm'])) . "</strong></div>";
            if ($record['time_out_pm'])
                $calendar .= "<div class='calendar-entry'><strong>" . date('h:i A', strtotime($record['time_out_pm'])) . "</strong></div>";
        }

        $calendar .= "</td>";
        $dayOfWeek++;
    }

    // Complete the last row
    while ($dayOfWeek < 7) {
        $calendar .= "<td></td>";
        $dayOfWeek++;
    }

    $calendar .= "</tr></tbody></table>";

    // Add monthly summary
    $calendar .= "<div class='month-summary'>
        <div class='row'>
            <div class='col-md-4'>
                <p><strong>Days Worked:</strong> {$daysWorked}</p>
                <p><strong>Saturdays:</strong> {$saturdays}</p>
            </div>
            <div class='col-md-4'>
                <p><strong>Regular Hours:</strong> " . formatDecimalToTime($totalHours) . "</p>
                <p><strong>With Saturday x2:</strong> " . formatDecimalToTime($totalHoursWithSat) . "</p>
            </div>
            <div class='col-md-4'>
                <p><strong>Minus Lunch:</strong> " . formatDecimalToTime($totalHoursMinusLunch) . "</p>
            </div>
        </div>
    </div></div>";

    $months[] = $calendar;
}

// After the foreach loop, add annual summary to the response
echo json_encode([
    'success' => true,
    'months' => $months,
    'annual_totals' => [
        'total_hours' => formatDecimalToTime($annualTotals['total_hours']),
        'total_with_saturday' => formatDecimalToTime($annualTotals['total_with_saturday']),
        'total_minus_lunch' => formatDecimalToTime($annualTotals['total_minus_lunch']),
        'total_days' => $annualTotals['total_days'],
        'total_saturdays' => $annualTotals['total_saturdays']
    ]
]);
