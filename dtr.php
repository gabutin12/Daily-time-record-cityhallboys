<?php
// dtr.php backup file

// Start session
session_start();

require_once "db_connect.php";

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'] ?? 'Guest';

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// Handle month overflow/underflow
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

function generateCalendarPHP($year, $month)
{
    $firstDay = date('w', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));

    $calendar = '<div class="card-header">';
    $calendar .= '<div class="month-nav">';
    $calendar .= '<a href="?month=' . ($month - 1) . '&year=' . $year . '" class="btn btn-primary btn-sm"><i class="fas fa-chevron-left"></i></a>';
    $calendar .= '<div class="dropdown">';
    $calendar .= '<h4 class="mb-0 dropdown-toggle" id="monthDropdown" data-bs-toggle="dropdown" aria-expanded="false">' . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . '</h4>';
    $calendar .= '<ul class="dropdown-menu" aria-labelledby="monthDropdown">';
    for ($m = 1; $m <= 12; $m++) {
        $calendar .= '<li><a class="dropdown-item" href="?month=' . $m . '&year=' . $year . '">' . date('F', mktime(0, 0, 0, $m, 1, $year)) . '</a></li>';
    }
    $calendar .= '</ul>';
    $calendar .= '</div>';
    $calendar .= '<a href="?month=' . ($month + 1) . '&year=' . $year . '" class="btn btn-primary btn-sm"><i class="fas fa-chevron-right"></i></a>';
    $calendar .= '</div></div>';

    $calendar .= '<div class="card-body p-0">';
    $calendar .= '<table class="calendar w-100">';
    $calendar .= '<thead><tr>';

    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    foreach ($days as $day) {
        $calendar .= "<th>$day</th>";
    }

    $calendar .= '</tr></thead><tbody><tr>';

    // Blank days
    for ($i = 0; $firstDay > $i; $i++) {
        $calendar .= '<td></td>';
    }

    $currentDay = 1;
    $dayOfWeek = $firstDay;

    while ($currentDay <= $daysInMonth) {
        if ($dayOfWeek == 7) {
            $dayOfWeek = 0;
            $calendar .= '</tr><tr>';
        }

        $date = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
        $today = ($date == date('Y-m-d')) ? ' bg-light' : '';
        $hasEntries = false;

        $calendar .= "<td class='$today'>";
        $calendar .= "<div class='day-header'>";
        $calendar .= $currentDay;

        // Get time records and check if entries exist
        if (isset($_SESSION['id'])) {
            $records = getTimeRecords($_SESSION['id'], $date);
            if ($records && ($records['time_in_am'] || $records['time_out_am'] ||
                $records['time_in_pm'] || $records['time_out_pm'])) {
                $hasEntries = true;
                $calendar .= "<button class='btn btn-sm btn-link delete-day' 
                             onclick='deleteDay(\"$date\")'>
                             <i class='fas fa-times'></i>
                          </button>";
            }
        }

        $calendar .= "</div>";
        $calendar .= "<div id='entry-$year-$month-$currentDay' class='entry-container'>";

        // Get time records for this date if they exist
        if (isset($_SESSION['id'])) {
            $records = getTimeRecords($_SESSION['id'], $date);
            if ($records) {
                if ($records['time_in_am']) {
                    $calendar .= "<div class='calendar-entry'>" . date('h:i A', strtotime($records['time_in_am'])) . "</div>";
                }
                if ($records['time_out_am']) {
                    $calendar .= "<div class='calendar-entry'>" . date('h:i A', strtotime($records['time_out_am'])) . "</div>";
                }
                if ($records['time_in_pm']) {
                    $calendar .= "<div class='calendar-entry'>" . date('h:i A', strtotime($records['time_in_pm'])) . "</div>";
                }
                if ($records['time_out_pm']) {
                    $calendar .= "<div class='calendar-entry'>" . date('h:i A', strtotime($records['time_out_pm'])) . "</div>";
                }
            }
        }

        $calendar .= "</div></td>";

        $currentDay++;
        $dayOfWeek++;
    }

    // Complete the table
    while ($dayOfWeek < 7) {
        $calendar .= '<td></td>';
        $dayOfWeek++;
    }

    $calendar .= '</tr></tbody></table></div>';

    return $calendar;
}

function getTimeRecords($user_id, $date)
{
    global $conn;
    $sql = "SELECT * FROM dtr_records WHERE user_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Add this function after getTimeRecords()
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

// Add after calculateTotalHours function
function calculateTotalHoursWithSaturday($user_id)
{
    global $conn;
    $sql = "SELECT 
            date, 
            total_hours,
            DAYOFWEEK(date) as day_of_week 
            FROM dtr_records 
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalHours = 0;
    while ($row = $result->fetch_assoc()) {
        // DAYOFWEEK returns 1 for Sunday, 7 for Saturday
        if ($row['day_of_week'] == 7) { // Saturday
            $totalHours += ($row['total_hours'] * 2);
        } else {
            $totalHours += $row['total_hours'];
        }
    }

    return number_format($totalHours, 2);
}

// Add this function after calculateTotalHoursWithSaturday
function calculateTotalHoursMinusLunch($user_id)
{
    global $conn;
    $sql = "SELECT 
            date, 
            total_hours,
            DAYOFWEEK(date) as day_of_week 
            FROM dtr_records 
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalHours = 0;
    while ($row = $result->fetch_assoc()) {
        // Subtract 1 hour lunch break
        $hoursForDay = $row['total_hours'] - 1;

        // Double hours for Saturday
        if ($row['day_of_week'] == 7) { // Saturday
            $hoursForDay = ($hoursForDay * 2);
        }

        $totalHours += $hoursForDay;
    }

    return number_format($totalHours, 2);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Time Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            /* Added padding */
            background-color: #f8f9fa;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: auto;
            /* Changed from calc(100vh - 40px) */
            gap: 20px;
            padding: 20px;
            margin: 0 auto;
            /* Center the container */
            max-width: 1800px;
            /* Limit maximum width */
            background-color: white;
            /* Add background */
            border-radius: 15px;
            /* Rounded corners */
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            /* Add shadow */
        }

        .sidebar {
            width: 400px;
            min-width: 400px;
            height: auto;
            /* Changed from calc(100vh - 80px) */
            overflow-y: visible;
            /* Changed from auto */
            position: sticky;
            top: 20px;
        }

        .main-content {
            flex: 1;
            height: auto;
            /* Changed from calc(100vh - 80px) */
            overflow-y: visible;
            /* Changed from auto */
        }

        .main-content .card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .main-content .card-body {
            flex: 1;
            padding: 0;
            overflow: auto;
        }

        .card {
            margin-bottom: 20px;
            height: auto;
            /* Changed from 100% */
        }

        .calendar {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .calendar th,
        .calendar td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            vertical-align: top;
            position: relative;
            height: 100px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .calendar td:hover {
            background-color: #e9ecef;
        }

        .day-header {
            position: absolute;
            top: 5px;
            right: 5px;
            font-weight: bold;
            font-size: 18px;
            /* Increased from 14px */
            color: #6c757d;
        }

        .time-entry {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .time-input {
            width: 50%;
        }

        .calendar-entry {
            font-size: 1.1rem;
            /* Increased from 0.8rem */
            padding: 4px 8px;
            /* Increased padding */
            margin: 3px 0;
            /* Increased margin */
            background: #198754;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            font-weight: 500;
            display: block;
            /* Ensures vertical stacking */
            width: 100%;
            /* Makes it full width */
            text-align: center;
            /* Centers text */
            margin: 5px 0;
            /* Adds spacing between entries */
        }

        .month-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .btn-sm {
            padding: 5px 10px;
        }

        .calendar th {
            background-color: #f8f9fa;
            padding: 12px;
            /* Increased from 10px */
            font-weight: 600;
            /* Increased from bold */
            border: 1px solid #dee2e6;
            font-size: 1.1rem;
            /* Added font size */
            color: #495057;
            /* Added text color */
            text-transform: uppercase;
            /* Optional: makes days uppercase */
        }

        .calendar td {
            height: 130px;
            /* Increased from 120px to accommodate larger entries */
            width: 14.28%;
            vertical-align: top;
            padding: 8px;
            /* Increased from 5px */
            border: 1px solid #dee2e6;
            position: relative;
        }

        .day-header {
            font-weight: bold;
            margin-bottom: 5px;
            text-align: right;
            color: #495057;
        }

        .entry-container {
            margin-top: 20px;
        }

        .calendar-entry {
            font-size: 1.1rem;
            /* Increased from 0.8rem */
            padding: 4px 8px;
            /* Increased padding */
            margin: 3px 0;
            /* Increased margin */
            background-color: #198754;
            color: white;
            border-radius: 5px;
            text-align: center;
        }

        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 10px;
        }

        .edit-day {
            padding: 2px 5px;
            font-size: 0.8rem;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .edit-day:hover {
            opacity: 1;
        }

        td:hover .edit-day {
            opacity: 1;
        }

        .delete-day {
            padding: 2px;
            font-size: 0.9rem;
            color: #dc3545;
            opacity: 0;
            transition: opacity 0.3s;
            border: none;
            background: none;
            position: absolute;
            top: 5px;
            right: 5px;
        }

        .delete-day:hover {
            color: #bb2d3b;
        }

        td:hover .delete-day {
            opacity: 1;
        }

        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 10px;
            padding-right: 25px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="card text-center p-3">
                <div class="card-header">
                    Welcome, <?php echo htmlspecialchars($username); ?>
                    <div id="realTimeClock" class="text-muted" style="font-size: 14px; color: black; margin-top: 5px;"></div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <button onclick="exportToExcel()" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header text-center bg-dark text-white">Daily Time Record</div>
                <div class="card-body">
                    <label class="form-label">Date:</label>
                    <input type="date"
                        class="form-control mb-3"
                        id="datePicker"
                        name="date"
                        value="<?php echo date('Y-m-d'); ?>"
                        required>

                    <div class="d-grid gap-3">
                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time In (AM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2" value="08:00">
                            <input type="hidden" name="time_type" value="time_in_am">
                            <input type="hidden" name="date" value="">

                        </form>

                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time Out (AM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2" value="12:00">
                            <input type="hidden" name="time_type" value="time_out_am">
                            <input type="hidden" name="date" value="">

                        </form>

                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time In (PM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2" value="12:00">
                            <input type="hidden" name="time_type" value="time_in_pm">
                            <input type="hidden" name="date" value="">

                        </form>

                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time Out (PM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2" value="17:30">
                            <input type="hidden" name="time_type" value="time_out_pm">
                            <input type="hidden" name="date" value="">

                        </form>
                    </div>
                    <button class="btn btn-warning w-100 mt-3" onclick="resetAllTimes()">Reset All</button>
                    <button class="btn btn-success w-100 mt-2" onclick="saveAllTimes()"><i class="fas fa-save"></i> Save All Times</button>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header text-center bg-info text-white">
                    <i class="fas fa-clock"></i> Total Hours Rendered w/o Sat X2
                </div>
                <div class="card-body text-center">
                    <h3 id="totalHours" class="fw-bold text-primary">
                        <?php echo calculateTotalHours($_SESSION['id']); ?> Hours
                    </h3>
                </div>
            </div>

            <!-- Add after the Total Hours Rendered card -->
            <div class="card mt-3">
                <div class="card-header text-center bg-warning text-white">
                    <i class="fas fa-clock"></i> Rendered Hours w/ Sat X2
                </div>
                <div class="card-body text-center">
                    <h3 id="totalHoursWithSaturday" class="fw-bold text-warning">
                        <?php echo calculateTotalHoursWithSaturday($_SESSION['id']); ?> Hours
                    </h3>
                </div>
            </div>

            <!-- Add after the Rendered Hours w/ Sat X2 card -->
            <div class="card mt-3">
                <div class="card-header text-center bg-danger text-white">
                    <i class="fas fa-clock"></i> Total Hours Minus Lunch Break
                </div>
                <div class="card-body text-center">
                    <h3 id="totalHoursMinusLunch" class="fw-bold text-danger">
                        <?php echo calculateTotalHoursMinusLunch($_SESSION['id']); ?> Hours
                    </h3>
                </div>
            </div>

        </div>

        <div class="main-content">
            <div class="card h-100">
                <?php echo generateCalendarPHP($year, $month); ?>
            </div>
        </div>
    </div>

    <script>
        // Add this inside your <script> tags
        document.addEventListener('DOMContentLoaded', function() {
            // Handle form submissions
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const selectedDate = document.getElementById('datePicker').value;
                    formData.append('date', selectedDate);

                    fetch('record_time.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Convert date parts
                                const dateParts = selectedDate.split('-');
                                const year = dateParts[0];
                                const month = dateParts[1];
                                const day = dateParts[2];

                                // Find the calendar entry container
                                const entryContainer = document.querySelector(`#entry-${year}-${month}-${day}`);
                                if (entryContainer) {
                                    // Clear existing entries
                                    entryContainer.innerHTML = '';

                                    // Add new time entries
                                    const timeEntries = {
                                        'time_in_am': 'IN (AM)',
                                        'time_out_am': 'OUT (AM)',
                                        'time_in_pm': 'IN (PM)',
                                        'time_out_pm': 'OUT (PM)'
                                    };

                                    for (const [key, label] of Object.entries(timeEntries)) {
                                        if (data.records[key]) {
                                            const time = new Date(`2000-01-01T${data.records[key]}`);
                                            const formattedTime = time.toLocaleTimeString([], {
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            });
                                            entryContainer.innerHTML += `
                                        <div class='calendar-entry'>
                                            ${label}: ${formattedTime}
                                        </div>`;
                                        }
                                    }
                                }

                                // Update total hours if available
                                if (data.records.total_hours) {
                                    document.getElementById('totalHours').textContent =
                                        `${parseFloat(data.records.total_hours).toFixed(2)} Hours`;
                                }
                            } else {
                                alert('Error recording time: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error saving time record');
                        });
                });
            });

            // Set initial date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('datePicker').value = today;

            // Update hidden date inputs
            document.querySelectorAll('input[type="hidden"][name="date"]')
                .forEach(input => input.value = today);
        });

        // Add function to format time
        function formatTime(timeStr) {
            const time = new Date(`2000-01-01T${timeStr}`);
            return time.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Add this function in your <script> section
        function refreshCalendar() {
            // Get current date from datepicker
            const currentDate = document.getElementById('datePicker').value;

            // Reload the page with current year and month
            const date = new Date(currentDate);
            const year = date.getFullYear();
            const month = date.getMonth() + 1;

            window.location.href = `dtr.php?year=${year}&month=${month}`;
        }

        // Add this function in your <script> section
        function saveAllTimes() {
            const date = document.getElementById('datePicker').value;
            const timeInAM = document.querySelector('input[name="time_value"][value="08:00"]').value;
            const timeOutAM = document.querySelector('input[name="time_value"][value="12:00"]').value;
            const timeInPM = document.querySelector('input[name="time_value"][value="12:00"]').value;
            const timeOutPM = document.querySelector('input[name="time_value"][value="17:30"]').value;

            const data = {
                date: date,
                times: {
                    time_in_am: timeInAM,
                    time_out_am: timeOutAM,
                    time_in_pm: timeInPM,
                    time_out_pm: timeOutPM
                }
            };

            fetch('save_all_times.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('All times saved successfully');

                        // Update all total hours displays
                        document.getElementById('totalHours').textContent =
                            `${parseFloat(data.total_hours).toFixed(2)} Hours`;
                        document.getElementById('totalHoursWithSaturday').textContent =
                            `${parseFloat(data.total_hours_with_saturday).toFixed(2)} Hours`;
                        document.getElementById('totalHoursMinusLunch').textContent =
                            `${parseFloat(data.total_hours_minus_lunch).toFixed(2)} Hours`;

                        // Add this line to refresh the page after saving
                        window.location.reload();

                        // Alternatively, you could use your existing refreshCalendar function:
                        // refreshCalendar();
                    } else {
                        alert('Error saving times: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving times');
                });
        }

        // Add this function in your <script> section
        function resetAllTimes() {
            if (confirm('Are you sure you want to reset all time entries? This action cannot be undone.')) {
                const selectedDate = document.getElementById('datePicker').value;

                fetch('reset_times.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            date: selectedDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Clear calendar entry for the selected date
                            const dateParts = selectedDate.split('-');
                            const year = dateParts[0];
                            const month = dateParts[1];
                            const day = dateParts[2];

                            const entryContainer = document.querySelector(`#entry-${year}-${month}-${day}`);
                            if (entryContainer) {
                                entryContainer.innerHTML = '';
                            }

                            // Reset all time inputs to default values
                            document.querySelector('input[name="time_value"][value="08:00"]').value = "08:00";
                            document.querySelector('input[name="time_value"][value="12:00"]').value = "12:00";
                            document.querySelector('input[name="time_value"][value="12:00"]').value = "12:00";
                            document.querySelector('input[name="time_value"][value="17:30"]').value = "17:30";

                            // Update total hours
                            document.getElementById('totalHours').textContent =
                                `${parseFloat(data.total_hours).toFixed(2)} Hours`;

                            alert('Time entries have been reset successfully');
                        } else {
                            alert('Error resetting times: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error resetting times');
                    });
            }
        }

        // Add this function in your <script> section
        function resetAllTimes() {
            if (confirm('Are you sure you want to reset all time records? This cannot be undone.')) {
                fetch('reset_entries.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Clear all calendar entries
                            document.querySelectorAll('.entry-container').forEach(container => {
                                container.innerHTML = '';
                            });

                            // Reset total hours
                            document.getElementById('totalHours').textContent = '0.00 Hours';

                            // Refresh the calendar
                            refreshCalendar();

                            alert('All time records have been reset');
                        } else {
                            alert('Error resetting records: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error resetting records');
                    });
            }
        }

        // Add this to your <script> section
        function editDay(date) {
            // Set the date picker to the selected date
            document.getElementById('datePicker').value = date;

            // Fetch existing records for this date
            fetch(`get_time_records.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.records) {
                        // Update time inputs with existing values
                        if (data.records.time_in_am) {
                            document.querySelector('input[name="time_value"][value="08:00"]').value =
                                data.records.time_in_am.substring(0, 5);
                        }
                        if (data.records.time_out_am) {
                            document.querySelector('input[name="time_value"][value="12:00"]').value =
                                data.records.time_out_am.substring(0, 5);
                        }
                        if (data.records.time_in_pm) {
                            document.querySelector('input[name="time_value"][value="12:00"]').value =
                                data.records.time_in_pm.substring(0, 5);
                        }
                        if (data.records.time_out_pm) {
                            document.querySelector('input[name="time_value"][value="17:30"]').value =
                                data.records.time_out_pm.substring(0, 5);
                        }
                    }

                    // Scroll to the time entry form
                    document.querySelector('.sidebar').scrollIntoView({
                        behavior: 'smooth'
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching time records');
                });
        }

        // Add this function to your <script> section
        function deleteDay(date) {
            if (confirm('Are you sure you want to delete all entries for this day?')) {
                fetch('delete_day.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            date: date
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            alert('Entries deleted successfully');

                            // Update all total hours displays
                            document.getElementById('totalHours').textContent =
                                `${parseFloat(data.total_hours).toFixed(2)} Hours`;
                            document.getElementById('totalHoursWithSaturday').textContent =
                                `${parseFloat(data.total_hours_with_saturday).toFixed(2)} Hours`;
                            document.getElementById('totalHoursMinusLunch').textContent =
                                `${parseFloat(data.total_hours_minus_lunch).toFixed(2)} Hours`;

                            // Get the date parts for the URL
                            const dateParts = date.split('-');
                            const year = dateParts[0];
                            const month = dateParts[1];

                            // Refresh the page with the current year and month
                            window.location.href = `dtr.php?year=${year}&month=${month}`;
                        } else {
                            alert('Error deleting entries: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting entries');
                    });
            }
        }

        // Add this to your <script> section
        function exportToExcel() {
            window.location.href = 'export_excel.php';
        }
    </script>
</body>

</html>
<!-- 
-- Insert test record
INSERT INTO dtr_records (user_id, date, time_in_am, time_out_am, time_in_pm, time_out_pm)
VALUES (1, CURDATE(), '08:00:00', '12:00:00', '13:00:00', '17:00:00');

-- Query to check total hours
SELECT
u.username,
d.date,
TIME_FORMAT(d.time_in_am, '%h:%i %p') as morning_in,
TIME_FORMAT(d.time_out_am, '%h:%i %p') as morning_out,
TIME_FORMAT(d.time_in_pm, '%h:%i %p') as afternoon_in,
TIME_FORMAT(d.time_out_pm, '%h:%i %p') as afternoon_out,
d.total_hours
FROM dtr_records d
JOIN users u ON d.user_id = u.id
ORDER BY d.date DESC; -->