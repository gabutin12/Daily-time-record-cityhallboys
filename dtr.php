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

        // Get time records before checking them
        $records = isset($_SESSION['id']) ? getTimeRecords($_SESSION['id'], $date) : null;

        $calendar .= "<td class='$today'>";
        $calendar .= "<div class='day-header'>";
        $calendar .= $currentDay;

        if ($records && ($records['time_in_am'] || $records['time_out_am'] ||
            $records['time_in_pm'] || $records['time_out_pm'])) {
            $calendar .= "<div class='button-container'>";
            $calendar .= "<button class='edit-day btn btn-outline-success btn-sm' onclick='showEditModal(\"$date\")'>
                            <i class='fas fa-edit'></i>
                        </button>";
            $calendar .= "<button class='journal-day btn btn-outline-info btn-sm' onclick='showJournalModal(\"$date\")'>
                            <i class='fas fa-book'></i>
                        </button>";
            $calendar .= "<button class='delete-day btn btn-outline-danger btn-sm' onclick='deleteDay(\"$date\")'>
                            <i class='fas fa-times'></i>
                        </button>";
            $calendar .= "</div>";
        } else {
            $calendar .= "<button class='add-day btn btn-outline-primary btn-sm' type='button' data-bs-toggle='modal' data-bs-target='#addTimeModal' onclick='initAddModal(\"$date\")'>
                            <i class='fas fa-plus'></i>
                        </button>";
        }

        $calendar .= "</div>";
        $calendar .= "<div id='entry-$year-$month-$currentDay' class='entry-container'>";

        // Display time records
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

// Add this function at the top with other functions
function formatHoursToTime($decimal_hours)
{
    $hours = floor($decimal_hours);
    $minutes = round(($decimal_hours - $hours) * 60);
    return sprintf("%d:%02d", $hours, $minutes);
}

// Update the calculation functions
function calculateTotalHours($user_id)
{
    global $conn;
    $sql = "SELECT SUM(total_hours) as total FROM dtr_records WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return formatHoursToTime($result['total'] ?? 0);
}

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

    return formatHoursToTime($totalHours);
}

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

    return formatHoursToTime($totalHours);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
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
            padding: 4px 8px;
            font-size: 0.9rem;
            opacity: 0;
            transition: all 0.3s ease;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .edit-day:hover {
            background-color: #198754;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        td:hover .edit-day {
            opacity: 1;
        }

        .delete-day {
            padding: 4px 8px;
            font-size: 0.9rem;
            opacity: 0;
            transition: all 0.3s ease;
            border-radius: 4px;
            border: 1px solid transparent;
            background: none;
        }

        .delete-day:hover {
            background-color: #dc3545;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        .button-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: 20px;
        }

        .add-day {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 8px 12px;
            font-size: 1rem;
            opacity: 0;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .add-day:hover {
            background-color: #0d6efd;
            color: white;
            transform: translate(-50%, -50%) scale(1.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        td:hover .add-day {
            opacity: 1;
        }

        .journal-day {
            padding: 4px 8px;
            font-size: 0.9rem;
            opacity: 0;
            transition: all 0.3s ease;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .journal-day:hover {
            background-color: #0dcaf0;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        td:hover .journal-day {
            opacity: 1;
        }

        .nav-tabs .nav-link {
            color: #495057;
            cursor: pointer;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            font-weight: 600;
        }

        .preview-container {
            background-color: #f8f9fa;
            border-radius: 4px;
            min-height: 400px;
        }

        #previewText {
            white-space: pre-wrap;
            font-family: inherit;
            line-height: 1.5;
        }

        /* Add to your existing styles */
        .preview-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #previewDate,
        #previewDepartment {
            min-width: 150px;
            padding: 0 5px;
        }

        #previewText {
            min-height: 200px;
            white-space: pre-wrap;
            line-height: 1.6;
            font-size: 0.95rem;
        }


        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            /* Changed from center */
        }

        .journal-text {
            min-height: 100px;
            /* Increase this value to make the cell taller */
            white-space: pre-wrap;
            line-height: 1.4;
            padding: 10px;
            font-size: 11px;
            text-align: left;
            margin-bottom: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            /* Change this from margin: 10px 0 */
            font-size: 11px;
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
                    <button onclick="printAllJournals()" class="btn btn-info">
                        <i class="fas fa-book"></i> Print All Journals
                    </button>
                    <button onclick="printAllMonths()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print All Months
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
                    <i class="fas fa-clock"></i> Total Hours Minus 1 HR Lunch Break
                </div>
                <div class="card-body text-center">
                    <h3 id="totalHoursMinusLunch" class="fw-bold text-danger">
                        <?php echo calculateTotalHoursMinusLunch($_SESSION['id']); ?> Hours
                    </h3>
                </div>
            </div>

            <!-- Add this card to the sidebar -->
            <div class="card mt-3">
                <div class="card-header text-center bg-primary text-white">
                    <i class="fas fa-chart-bar"></i> Monthly Statistics
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Days Worked
                            <span class="badge bg-primary rounded-pill" id="daysWorked">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Average Hours/Day
                            <span class="badge bg-info rounded-pill" id="avgHours">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Saturdays Worked
                            <span class="badge bg-warning rounded-pill" id="saturdaysWorked">0</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

        <div class="main-content">
            <div class="card h-100">
                <?php echo generateCalendarPHP($year, $month); ?>
            </div>
        </div>
    </div>

    <!-- Add this right after your existing script tags and before the modals HTML -->
    <script>
        // Global variables
        // let currentEditDate = '';
        // let addTimeModal;
        let editTimeModal;

        // Initialize modals when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap modals
            addTimeModal = new bootstrap.Modal(document.getElementById('addTimeModal'));
            editTimeModal = new bootstrap.Modal(document.getElementById('editTimeModal'));

            // Add quack sound to all buttons
            document.querySelectorAll('button, .btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    playQuack();
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            journalModal = new bootstrap.Modal(document.getElementById('journalModal'));
        });

        function initAddModal(date) {
            currentEditDate = date;
            // Update all date inputs in the add modal
            document.querySelectorAll('#addTimeModal [name="date"]').forEach(input => {
                input.value = date;
            });
        }

        function saveAddTimes() {
            const forms = document.querySelectorAll('#addTimeModal form');
            const times = {};

            forms.forEach(form => {
                const timeType = form.querySelector('[name="time_type"]').value;
                times[timeType] = form.querySelector('[name="time_value"]').value;
            });

            const data = {
                date: currentEditDate,
                times: times
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
                        addTimeModal.hide();
                        const dateParts = currentEditDate.split('-');
                        const year = dateParts[0];
                        const month = dateParts[1];
                        window.location.href = `dtr.php?year=${year}&month=${month}`;
                    } else {
                        alert('Error saving times: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving times');
                });
        }

        // Add this function to calculate monthly statistics
        function updateMonthlyStatistics() {
            const year = new URLSearchParams(window.location.search).get('year') || new Date().getFullYear();
            const month = new URLSearchParams(window.location.search).get('month') || (new Date().getMonth() + 1);

            fetch(`get_monthly_stats.php?year=${year}&month=${month}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('daysWorked').textContent = data.daysWorked;
                    document.getElementById('avgHours').textContent = formatHoursToTime(data.averageHours);
                    document.getElementById('saturdaysWorked').textContent = data.saturdaysWorked;
                })
                .catch(error => console.error('Error:', error));
        }

        // Call this when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateMonthlyStatistics();
        });

        // Add this function to your JavaScript section
        function printAllMonths() {
            const currentYear = new Date().getFullYear();
            const printWindow = window.open('', '', 'height=600,width=800');

            fetch(`get_all_months.php?year=${currentYear}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        printWindow.document.write(`
                            <html>
                                <head>
                                    <title>DTR - Annual Report ${currentYear}</title>
                                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
                                    <style>
                                        @page { size: landscape; margin: 15mm; }
                                        body {
                                            padding: 20px;
                                            font-family: Arial, sans-serif;
                                            background: white;
                                        }
                                        .month-section {
                                            page-break-after: always;
                                            margin-bottom: 30px;
                                            border: 1px solid black;
                                            padding: 15px;
                                        }
                                        .calendar {
                                            width: 100%;
                                            border-collapse: collapse;
                                            margin-bottom: 20px;
                                            border: 2px solid black;
                                        }
                                        .calendar th, .calendar td {
                                            border: 1px solid black;
                                            padding: 8px;
                                            font-size: 12px;
                                        }
                                        .calendar th {
                                            background-color: #E8F4F8;
                                            font-weight: bold;
                                            border: 1px solid black;
                                        }
                                        .calendar-entry {
                                            text-align: center;
                                            padding: 3px;
                                            margin: 2px 0;
                                            border: 1px solid black;
                                            border-radius: 3px;
                                            font-size: 11px;
                                            color: black;
                                            font-weight: bold;
                                        }
                                        .month-header {
                                            text-align: center;
                                            margin: 20px 0;
                                            padding: 10px;
                                            color: black !important;
                                            border-radius: 5px;
                                            border: 2px solid black;
                                            -webkit-print-color-adjust: exact;
                                            print-color-adjust: exact;
                                        }
                                        .annual-summary {
                                            margin-top: 30px;
                                            padding: 20px;
                                            border: 2px solid black;
                                            border-radius: 5px;
                                        }
                                        @media print {
                                            .no-print { display: none; }
                                            .month-section { page-break-after: always; }
                                            .month-header {
                                                color: black !important;
                                                border: 2px solid black !important;
                                                -webkit-print-color-adjust: exact;
                                                print-color-adjust: exact;
                                            }
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="report-header">
                                        <h2 class="text-center mb-4">Annual Daily Time Record - ${currentYear}</h2>
                                        <p class="text-center">Employee: ${document.querySelector('.card-header').textContent.split(',')[1]}</p>
                                        <p class="text-center">Generated on: ${new Date().toLocaleDateString()}</p>
                                    </div>
                                    ${data.months.join('')}
                                    <div class="annual-summary">
                                        <h2 class="text-center">Annual Summary</h2>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Total Days Worked:</strong> ${data.annual_totals.total_days}</p>
                                                <p><strong>Total Saturdays:</strong> ${data.annual_totals.total_saturdays}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Total Regular Hours:</strong> ${data.annual_totals.total_hours}</p>
                                                <p><strong>Total Hours with Saturday x2:</strong> ${data.annual_totals.total_with_saturday}</p>
                                                <p><strong>Total Hours Minus Lunch:</strong> ${data.annual_totals.total_minus_lunch}</p>
                                            </div>
                                        </div>
                                    </div>
                                </body>
                            </html>
                        `);

                        printWindow.document.close();
                        setTimeout(() => {
                            printWindow.print();
                            printWindow.close();
                        }, 1000);
                    } else {
                        alert('Error generating print view');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error generating print view');
                });
        }

        // Add these functions to your existing JavaScript
        let journalModal;

        document.addEventListener('DOMContentLoaded', function() {
            journalModal = new bootstrap.Modal(document.getElementById('journalModal'));
        });

        function showJournalModal(date) {
            document.getElementById('journalDate').value = date;

            fetch(`get_journal.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.journal) {
                        document.getElementById('name').value = data.journal.name || '';
                        document.getElementById('hteName').value = data.journal.hte_name || ''; // Add this line
                        document.getElementById('department').value = data.journal.department || '';
                        document.getElementById('journalText').value = data.journal.text || '';
                    } else {
                        document.getElementById('name').value = '';
                        document.getElementById('hteName').value = ''; // Add this line
                        document.getElementById('department').value = '';
                        document.getElementById('journalText').value = '';
                    }
                    const journalModal = new bootstrap.Modal(document.getElementById('journalModal'));
                    journalModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading journal entry');
                });
        }

        function submitJournal() {
            const date = document.getElementById('journalDate').value;
            const name = document.getElementById('name').value;
            const department = document.getElementById('department').value;
            const text = document.getElementById('journalText').value;

            if (!name || !department || !text) {
                alert('Please fill in all fields');
                return;
            }

            fetch('save_journal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        date: date,
                        name: name,
                        department: department,
                        text: text
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Journal entry saved successfully');
                        bootstrap.Modal.getInstance(document.getElementById('journalModal')).hide();
                        // Optionally refresh the page or update UI
                        window.location.reload();
                    } else {
                        alert('Error saving journal entry: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving journal entry');
                });
        }

        // Add this function in your JavaScript section
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
                            `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;
                        document.getElementById('totalHoursWithSaturday').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_with_saturday))} Hours`;
                        document.getElementById('totalHoursMinusLunch').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_minus_lunch))} Hours`;

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
                                `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;

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
                                `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;
                            document.getElementById('totalHoursWithSaturday').textContent =
                                `${formatHoursToTime(parseFloat(data.total_hours_with_saturday))} Hours`;
                            document.getElementById('totalHoursMinusLunch').textContent =
                                `${formatHoursToTime(parseFloat(data.total_hours_minus_lunch))} Hours`;

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

        // Add to your <script> section
        let currentEditDate = '';
        const editModal = new bootstrap.Modal(document.getElementById('editTimeModal'));

        function showEditModal(date) {
            currentEditDate = date;
            const modalElement = document.getElementById('editTimeModal');

            // Debug log
            console.log('Opening modal for date:', date);

            // Initialize modal if not already initialized
            if (!editTimeModal) {
                editTimeModal = new bootstrap.Modal(modalElement);
            }

            // Fetch existing records
            fetch(`get_time_records.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Fetched data:', data);
                    if (data.success && data.records) {
                        // Update time inputs
                        modalElement.querySelector('input[name="time_type"][value="time_in_am"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_in_am ? data.records.time_in_am.substring(0, 5) : '08:00';

                        modalElement.querySelector('input[name="time_type"][value="time_out_am"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_out_am ? data.records.time_out_am.substring(0, 5) : '12:00';

                        modalElement.querySelector('input[name="time_type"][value="time_in_pm"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_in_pm ? data.records.time_in_pm.substring(0, 5) : '13:00';

                        modalElement.querySelector('input[name="time_type"][value="time_out_pm"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_out_pm ? data.records.time_out_pm.substring(0, 5) : '17:30';

                        editTimeModal.show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching time records');
                });
        }

        function saveModalTimes() {
            const modalElement = document.getElementById('editTimeModal');

            const times = {
                time_in_am: modalElement.querySelector('input[name="time_type"][value="time_in_am"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value,
                time_out_am: modalElement.querySelector('input[name="time_type"][value="time_out_am"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value,
                time_in_pm: modalElement.querySelector('input[name="time_type"][value="time_in_pm"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value,
                time_out_pm: modalElement.querySelector('input[name="time_type"][value="time_out_pm"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value
            };

            const data = {
                date: currentEditDate,
                times: times
            };

            fetch('save_all_times.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                }))
                .then(data => {
                    if (data.success) {
                        editTimeModal.hide();
                        // Update total hours displays
                        document.getElementById('totalHours').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;
                        document.getElementById('totalHoursWithSaturday').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_with_saturday))} Hours`;
                        document.getElementById('totalHoursMinusLunch').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_minus_lunch))} Hours`;
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving times: ' + error.message);
                });
        }

        // Add this function to your JavaScript
        function formatHoursToTime(decimal_hours) {
            const hours = Math.floor(decimal_hours);
            const minutes = Math.round((decimal_hours - hours) * 60);
            return `${hours}:${minutes.toString().padStart(2, '0')}`;
        }




        document.addEventListener('DOMContentLoaded', function() {
            addTimeModal = new bootstrap.Modal(document.getElementById('addTimeModal'));
        });

        function showAddModal(date) {
            currentEditDate = date; // Reuse the existing variable
            document.querySelectorAll('#addTimeModal [name="date"]').forEach(input => {
                input.value = date;
            });
            addTimeModal.show();
        }

        function saveAddTimes() {
            const forms = document.querySelectorAll('#addTimeModal form');
            const times = {};

            forms.forEach(form => {
                const timeType = form.querySelector('[name="time_type"]').value;
                times[timeType] = form.querySelector('[name="time_value"]').value;
            });

            const data = {
                date: currentEditDate,
                times: times
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
                        addTimeModal.hide();
                        const dateParts = currentEditDate.split('-');
                        const year = dateParts[0];
                        const month = dateParts[1];
                        window.location.href = `dtr.php?year=${year}&month=${month}`;
                    } else {
                        alert('Error saving times: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving times');
                });
        }

        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const dateString = now.toLocaleDateString();
            document.getElementById('realTimeClock').innerHTML = `${dateString} ${timeString}`;
        }

        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial call

        function printCurrentMonth() {
            const year = new URLSearchParams(window.location.search).get('year') || new Date().getFullYear();
            const month = new URLSearchParams(window.location.search).get('month') || (new Date().getMonth() + 1);

            const content = document.querySelector('.main-content').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=800');

            printWindow.document.write(`
                <html>
                    <head>
                        <title>DTR - ${document.querySelector('#monthDropdown').textContent}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            @page { size: landscape; }
                            body {
                                padding: 20px;
                                font-family: Arial, sans-serif;
                            }
                            .calendar {
                                width: 100%;
                                border-collapse: collapse;
                            }
                            .calendar th, .calendar td {
                                border: 1px solid #dee2e6;
                                padding: 8px;
                            }
                            .calendar th {
                                background-color: #f8f9fa;
                                font-weight: bold;
                            }
                            .calendar-entry {
                                background: #198754;
                                color: white;
                                padding: 4px;
                                margin: 2px 0;
                                border-radius: 4px;
                                font-size: 12px;
                            }
                            .summary-section {
                                margin-top: 20px;
                                padding: 15px;
                                border: 1px solid #dee2e6;
                                border-radius: 5px;
                            }
                            .header-section {
                                text-align: center;
                                margin-bottom: 20px;
                            }
                            .header-section h2 {
                                color: #333;
                                margin-bottom: 5px;
                            }
                            .header-section p {
                                color: #666;
                                margin: 0;
                            }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header-section">
                            <h2>Daily Time Record</h2>
                            <p>${document.querySelector('#monthDropdown').textContent}</p>
                            <p>Employee: ${document.querySelector('.card-header').textContent.split(',')[1]}</p>
                        </div>
                        ${content}
                        <div class="summary-section">
                            <h4>Monthly Summary</h4>
                            <p>Days Worked: ${document.getElementById('daysWorked').textContent}</p>
                            <p>Average Hours/Day: ${document.getElementById('avgHours').textContent}</p>
                            <p>Saturdays Worked: ${document.getElementById('saturdaysWorked').textContent}</p>
                            <p>Total Hours: ${document.getElementById('totalHours').textContent}</p>
                            <p>Total Hours with Saturday x2: ${document.getElementById('totalHoursWithSaturday').textContent}</p>
                            <p>Total Hours Minus Lunch: ${document.getElementById('totalHoursMinusLunch').textContent}</p>
                        </div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 1000);
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch (e.key) {
                    case 's':
                        e.preventDefault();
                        saveAllTimes();
                        break;
                    case 'p':
                        e.preventDefault();
                        printCurrentMonth();
                        break;
                    case 'e':
                        e.preventDefault();
                        exportToExcel();
                        break;
                }
            }
        });

        // Add this with your other JavaScript functions
        function playQuack() {
            const audio = document.getElementById('quackSound');
            audio.currentTime = 0; // Reset sound to start
            audio.play();
        }

        // Add this to your JavaScript
        document.addEventListener('click', function(e) {
            if (e.target.matches('button') || e.target.matches('.btn') || e.target.closest('button') || e.target.closest('.btn')) {
                playQuack();
            }
        });
    </script>

    <!-- Update the edit modal HTML structure -->
    <div class="modal fade" id="editTimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Time Entries</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-3">
                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time In (AM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2">
                            <input type="hidden" name="time_type" value="time_in_am">
                        </form>

                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time Out (AM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2">
                            <input type="hidden" name="time_type" value="time_out_am">
                        </form>

                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time In (PM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2">
                            <input type="hidden" name="time_type" value="time_in_pm">
                        </form>

                        <form method="POST" class="d-flex align-items-center">
                            <label class="me-2">Time Out (PM):</label>
                            <input type="time" name="time_value" class="form-control time-input me-2">
                            <input type="hidden" name="time_type" value="time_out_pm">
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="saveModalTimes()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Time Modal -->
    <div class="modal fade" id="addTimeModal" tabindex="-1" aria-labelledby="addTimeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTimeModalLabel">Add Time Entries</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveAddTimes()">
                        <i class="fas fa-plus"></i> Add Times
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update the journal modal HTML -->
    <div class="modal fade" id="journalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Journal Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#write">Write</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#preview" onclick="updatePreview()">Preview</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="write">
                            <form id="journalForm">
                                <input type="hidden" id="journalDate" name="date">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name:</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="hteName" class="form-label">HTE Name:</label>
                                    <input type="text" class="form-control" id="hteName" name="hteName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department:</label>
                                    <input type="text" class="form-control" id="department" name="department" required>
                                </div>
                                <div class="mb-3">
                                    <label for="journalText" class="form-label">Write your Journal/Diary below:</label>
                                    <textarea class="form-control" id="journalText" name="journalText" rows="10" required></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="preview">
                            <!-- Preview content will be dynamically updated -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="deleteJournal()">Delete</button>
                    <button type="button" class="btn btn-success" onclick="submitJournal()">Save Entry</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add these before closing body tag -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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
                            `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;
                        document.getElementById('totalHoursWithSaturday').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_with_saturday))} Hours`;
                        document.getElementById('totalHoursMinusLunch').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_minus_lunch))} Hours`;

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
                                `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;

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
                                `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;
                            document.getElementById('totalHoursWithSaturday').textContent =
                                `${formatHoursToTime(parseFloat(data.total_hours_with_saturday))} Hours`;
                            document.getElementById('totalHoursMinusLunch').textContent =
                                `${formatHoursToTime(parseFloat(data.total_hours_minus_lunch))} Hours`;

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



        function showEditModal(date) {
            currentEditDate = date;
            const modalElement = document.getElementById('editTimeModal');

            // Debug log
            console.log('Opening modal for date:', date);

            // Initialize modal if not already initialized
            if (!editTimeModal) {
                editTimeModal = new bootstrap.Modal(modalElement);
            }

            // Fetch existing records
            fetch(`get_time_records.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Fetched data:', data);
                    if (data.success && data.records) {
                        // Update time inputs
                        modalElement.querySelector('input[name="time_type"][value="time_in_am"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_in_am ? data.records.time_in_am.substring(0, 5) : '08:00';

                        modalElement.querySelector('input[name="time_type"][value="time_out_am"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_out_am ? data.records.time_out_am.substring(0, 5) : '12:00';

                        modalElement.querySelector('input[name="time_type"][value="time_in_pm"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_in_pm ? data.records.time_in_pm.substring(0, 5) : '13:00';

                        modalElement.querySelector('input[name="time_type"][value="time_out_pm"]')
                            .closest('form')
                            .querySelector('input[name="time_value"]').value =
                            data.records.time_out_pm ? data.records.time_out_pm.substring(0, 5) : '17:30';

                        editTimeModal.show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching time records');
                });
        }

        function saveModalTimes() {
            const modalElement = document.getElementById('editTimeModal');

            const times = {
                time_in_am: modalElement.querySelector('input[name="time_type"][value="time_in_am"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value,
                time_out_am: modalElement.querySelector('input[name="time_type"][value="time_out_am"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value,
                time_in_pm: modalElement.querySelector('input[name="time_type"][value="time_in_pm"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value,
                time_out_pm: modalElement.querySelector('input[name="time_type"][value="time_out_pm"]')
                    .closest('form')
                    .querySelector('input[name="time_value"]').value
            };

            const data = {
                date: currentEditDate,
                times: times
            };

            fetch('save_all_times.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                }))
                .then(data => {
                    if (data.success) {
                        editTimeModal.hide();
                        // Update total hours displays
                        document.getElementById('totalHours').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours))} Hours`;
                        document.getElementById('totalHoursWithSaturday').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_with_saturday))} Hours`;
                        document.getElementById('totalHoursMinusLunch').textContent =
                            `${formatHoursToTime(parseFloat(data.total_hours_minus_lunch))} Hours`;
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving times: ' + error.message);
                });
        }

        // Add this function to your JavaScript
        function formatHoursToTime(decimal_hours) {
            const hours = Math.floor(decimal_hours);
            const minutes = Math.round((decimal_hours - hours) * 60);
            return `${hours}:${minutes.toString().padStart(2, '0')}`;
        }


        let addTimeModal;

        document.addEventListener('DOMContentLoaded', function() {
            addTimeModal = new bootstrap.Modal(document.getElementById('addTimeModal'));
        });

        function showAddModal(date) {
            currentEditDate = date; // Reuse the existing variable
            document.querySelectorAll('#addTimeModal [name="date"]').forEach(input => {
                input.value = date;
            });
            addTimeModal.show();
        }

        function saveAddTimes() {
            const forms = document.querySelectorAll('#addTimeModal form');
            const times = {};

            forms.forEach(form => {
                const timeType = form.querySelector('[name="time_type"]').value;
                times[timeType] = form.querySelector('[name="time_value"]').value;
            });

            const data = {
                date: currentEditDate,
                times: times
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
                        addTimeModal.hide();
                        const dateParts = currentEditDate.split('-');
                        const year = dateParts[0];
                        const month = dateParts[1];
                        window.location.href = `dtr.php?year=${year}&month=${month}`;
                    } else {
                        alert('Error saving times: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving times');
                });
        }

        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const dateString = now.toLocaleDateString();
            document.getElementById('realTimeClock').innerHTML = `${dateString} ${timeString}`;
        }

        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial call

        function printCurrentMonth() {
            const year = new URLSearchParams(window.location.search).get('year') || new Date().getFullYear();
            const month = new URLSearchParams(window.location.search).get('month') || (new Date().getMonth() + 1);

            const content = document.querySelector('.main-content').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=800');

            printWindow.document.write(`
                <html>
                    <head>
                        <title>DTR - ${document.querySelector('#monthDropdown').textContent}</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            @page { size: landscape; }
                            body {
                                padding: 20px;
                                font-family: Arial, sans-serif;
                            }
                            .calendar {
                                width: 100%;
                                border-collapse: collapse;
                            }
                            .calendar th, .calendar td {
                                border: 1px solid #dee2e6;
                                padding: 8px;
                            }
                            .calendar th {
                                background-color: #f8f9fa;
                                font-weight: bold;
                            }
                            .calendar-entry {
                                background: #198754;
                                color: white;
                                padding: 4px;
                                margin: 2px 0;
                                border-radius: 4px;
                                font-size: 12px;
                            }
                            .summary-section {
                                margin-top: 20px;
                                padding: 15px;
                                border: 1px solid #dee2e6;
                                border-radius: 5px;
                            }
                            .header-section {
                                text-align: center;
                                margin-bottom: 20px;
                            }
                            .header-section h2 {
                                color: #333;
                                margin-bottom: 5px;
                            }
                            .header-section p {
                                color: #666;
                                margin: 0;
                            }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header-section">
                            <h2>Daily Time Record</h2>
                            <p>${document.querySelector('#monthDropdown').textContent}</p>
                            <p>Employee: ${document.querySelector('.card-header').textContent.split(',')[1]}</p>
                        </div>
                        ${content}
                        <div class="summary-section">
                            <h4>Monthly Summary</h4>
                            <p>Days Worked: ${document.getElementById('daysWorked').textContent}</p>
                            <p>Average Hours/Day: ${document.getElementById('avgHours').textContent}</p>
                            <p>Saturdays Worked: ${document.getElementById('saturdaysWorked').textContent}</p>
                            <p>Total Hours: ${document.getElementById('totalHours').textContent}</p>
                            <p>Total Hours with Saturday x2: ${document.getElementById('totalHoursWithSaturday').textContent}</p>
                            <p>Total Hours Minus Lunch: ${document.getElementById('totalHoursMinusLunch').textContent}</p>
                        </div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 1000);
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch (e.key) {
                    case 's':
                        e.preventDefault();
                        saveAllTimes();
                        break;
                    case 'p':
                        e.preventDefault();
                        printCurrentMonth();
                        break;
                    case 'e':
                        e.preventDefault();
                        exportToExcel();
                        break;
                }
            }
        });


        // Update the updatePreview function
        function updatePreview() {
            const date = new Date(document.getElementById('journalDate').value).toLocaleDateString();
            const name = document.getElementById('name').value;
            const department = document.getElementById('department').value;
            const text = document.getElementById('journalText').value;

            document.getElementById('previewDate').textContent = date;
            document.getElementById('previewName').textContent = name;
            document.getElementById('previewDepartment').textContent = department;
            document.getElementById('previewText').innerHTML = text.replace(/\n/g, '<br>');
        }


        // Replace your existing showJournalModal function with this:
        function showJournalModal(date) {
            // Initialize modal if not already done
            if (!journalModal) {
                journalModal = new bootstrap.Modal(document.getElementById('journalModal'));
            }

            // Set the date
            document.getElementById('journalDate').value = date;

            // Clear form fields first
            document.getElementById('name').value = '';
            document.getElementById('department').value = '';
            document.getElementById('journalText').value = '';

            // Fetch journal entry
            fetch(`get_journal.php?date=${date}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.journal) {
                        document.getElementById('name').value = data.journal.name || '';
                        document.getElementById('department').value = data.journal.department || '';
                        document.getElementById('journalText').value = data.journal.text || '';
                    }
                    // Show modal regardless of whether entry exists
                    journalModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading journal entry: ' + error.message);
                });
        }

        // Update the submitJournal function
        function submitJournal() {
            const date = document.getElementById('journalDate').value;
            const name = document.getElementById('name').value;
            const hteName = document.getElementById('hteName').value; // Add this line
            const department = document.getElementById('department').value;
            const text = document.getElementById('journalText').value;

            if (!name || !hteName || !department || !text) { // Updated validation
                alert('Please fill in all fields');
                return;
            }

            fetch('save_journal.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        date: date,
                        name: name,
                        hte_name: hteName, // Add this line
                        department: department,
                        text: text
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Journal entry saved successfully');
                        journalModal.hide();
                        window.location.reload();
                    } else {
                        alert('Error saving journal entry: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving journal entry');
                });
        }

        function clearJournal() {
            // Clear form inputs without deleting from database
            document.getElementById('department').value = '';
            document.getElementById('journalText').value = '';
            updatePreview();
        }

        // Replace or update your existing deleteJournal function
        function deleteJournal() {
            if (confirm('Are you sure you want to delete this journal entry? This cannot be undone.')) {
                const date = document.getElementById('journalDate').value;

                fetch('delete_journal.php', {
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
                            alert('Journal entry deleted successfully');
                            // Close the modal
                            const journalModal = bootstrap.Modal.getInstance(document.getElementById('journalModal'));
                            journalModal.hide();
                            // Optionally refresh the page
                            window.location.reload();
                        } else {
                            alert('Error deleting journal entry: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting journal entry: ' + error.message);
                    });
            }
        }

        function printAllJournals() {
            const printWindow = window.open('', '', 'height=600,width=800');

            fetch('get_all_journals.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Group journals into sets of 3
                        const journalGroups = [];
                        for (let i = 0; i < data.journals.length; i += 3) {
                            journalGroups.push(data.journals.slice(i, i + 3));
                        }

                        printWindow.document.write(`
                            <html>
                                <head>
                                    <title>Practicum Journal/Diary</title>
                                    <style>
                                        @page {
                                            size: 8.5in 11in;  /* Letter size / Short bondpaper */
                                            margin: 0.5in;     /* 0.5 inch margins on all sides */
                                        }
                                        body {
                                            font-family: Arial, sans-serif;
                                            padding: 0;
                                            margin: 0;
                                            width: 7.5in;      /* 8.5in - (2 * 0.5in margin) */
                                            margin: 0 auto;
                                        }
                                        .page {
                                            page-break-after: always;
                                            padding: 0;
                                        }
                                        .journal-entry {
                                            margin-bottom: 20px;
                                        }
                                        .journal-text {
                                            min-height: 100px;
                                            white-space: pre-wrap;
                                            line-height: 1.4;
                                            padding: 10px;
                                            font-size: 11px;
                                        }
                                        table {
                                            width: 100%;
                                            border-collapse: collapse;
                                            margin: 0;
                                            font-size: 11px;
                                        }
                                        th, td {
                                            border: 1px solid black;
                                            padding: 8px;
                                            text-align: left;
                                        }
                                    </style>
                                </head>
                                <body>
                                    <!-- First page with main header -->
                                    <div class="page">
                                        <div style="text-align: center; margin-bottom: 30px;">
                                            <h1>Practicum Journal/Diary</h1>
                                        </div>
                                    </div>

                                    <!-- Journal entries pages -->
                                    ${journalGroups.map(group => `
                                        <div class="page">
                                            ${group.map(journal => `
                                                <div class="journal-entry">
                                                    <table>
                                                        <tr>
                                                            <th width="15%">Date:</th>
                                                            <td width="20%">${new Date(journal.date).toLocaleDateString()}</td>
                                                            <th width="10%">Department:</th>
                                                            <td width="55%" colspan="5">${journal.department}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>Time in (start):</th>
                                                            <td>${journal.time_in_am || ''}</th>
                                                            <th>Time out:</th>
                                                            <td>${journal.time_out_am || ''}</th>
                                                            <th>Time in:</th>
                                                            <td>${journal.time_in_pm || ''}</th>
                                                            <th>Time out (end):</th>
                                                            <td>${journal.time_out_pm || ''}</th>
                                                        </tr>
                                                        <tr>
                                                            <th colspan="8" style="text-align: left;">Write your Journal/Diary below:</th>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="8">
                                                                <div class="journal-text">${journal.text.replace(/\n/g, '<br>')}</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table style="width: 100%; border-collapse: collapse;">
                                                        <tr>
                                                            <td width="33.33%" style="text-align: center;">Student-Trainee's Signature</td>
                                                            <td width="33.33%" style="text-align: center;">HTE Trainer's Signature</td>
                                                            <td width="33.33%" style="text-align: center;">FPC's Signature</td>
                                                        </tr>
                                                        <tr>
                                                            <td height="50px" style="border: 1px solid black; text-align: center; vertical-align: middle;">${journal.name || ''}</td>
                                                            <td height="50px" style="border: 1px solid black; text-align: center; vertical-align: middle;">${journal.hte_name || ''}</td>
                                                            <td height="50px" style="border: 1px solid black;"></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            `).join('')}
                                        </div>
                                    `).join('')}
                                </body>
                            </html>
                        `);

                        printWindow.document.close();
                        setTimeout(() => {
                            printWindow.print();
                            printWindow.close();
                        }, 1000);
                    } else {
                        alert('Error loading journal entries');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading journal entries');
                });
        }
    </script>

    <audio id="quackSound" preload="auto">
        <source src="quack.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
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