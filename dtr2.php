<?php
// dtr.php

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Welcome message
$username = $_SESSION['username'];


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
            background-color: #f8f9fa;
            height: 100vh;
        }

        .container {
            display: flex;
            height: 100%;
            gap: 20px;
        }

        .sidebar {
            width: 35%;
            padding: 20px;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            width: 100%;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
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
            font-size: 14px;
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
            font-size: 0.9rem;
            margin-top: 5px;
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
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
            <div class="card mt-3">
                <div class="card-header text-center bg-dark text-white">Daily Time Record</div>
                <div class="card-body">
                    <label class="form-label">Date:</label>
                    <input type="date" class="form-control mb-3" id="datePicker" required>

                    <div class="d-grid gap-3">
                        <div class="d-flex align-items-center">
                            <label class="me-2">Time In (AM):</label>
                            <input type="time" class="form-control time-input me-2" id="timeInAM" value="08:00" readonly>
                            <button class="btn btn-success btn-sm" onclick="setTime('AM_IN')"><i class="fas fa-check"></i></button>
                        </div>

                        <div class="d-flex align-items-center">
                            <label class="me-2">Time Out (AM):</label>
                            <input type="time" class="form-control time-input me-2" id="timeOutAM" value="12:00" readonly>
                            <button class="btn btn-success btn-sm" onclick="setTime('AM_OUT')"><i class="fas fa-check"></i></button>
                        </div>

                        <div class="d-flex align-items-center">
                            <label class="me-2">Time In (PM):</label>
                            <input type="time" class="form-control time-input me-2" id="timeInPM" value="12:01" readonly>
                            <button class="btn btn-success btn-sm" onclick="setTime('PM_IN')"><i class="fas fa-check"></i></button>
                        </div>

                        <div class="d-flex align-items-center">
                            <label class="me-2">Time Out (PM):</label>
                            <input type="time" class="form-control time-input me-2" id="timeOutPM" value="17:00" readonly>
                            <button class="btn btn-success btn-sm" onclick="setTime('PM_OUT')"><i class="fas fa-check"></i></button>
                        </div>
                    </div>
                    <button class="btn btn-warning w-100 mt-3" onclick="resetAllTimes()">Reset All</button>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header text-center bg-info text-white">
                    <i class="fas fa-clock"></i> Total Hours Rendered
                </div>
                <div class="card-body text-center">
                    <h3 id="totalHours" class="fw-bold text-primary">0 Hours</h3>
                </div>
            </div>

        </div>

        <div class="main-content">
            <div class="card h-100">
                <div class="card-header text-center month-nav">
                    <button class="btn btn-primary btn-sm" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <h4 id="currentMonthYear" class="m-0"></h4>
                    <button class="btn btn-primary btn-sm" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="card-body">
                    <table class="calendar w-100">
                        <thead>
                            <tr>
                                <th>Sun</th>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                                <th>Sat</th>
                            </tr>
                        </thead>
                        <tbody id="calendarBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentDate = new Date();

        function generateCalendar(year, month) {
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            let calendarBody = document.getElementById("calendarBody");
            calendarBody.innerHTML = "";
            let dayCounter = 1;

            for (let i = 0; i < 6; i++) {
                let row = "<tr>";
                for (let j = 0; j < 7; j++) {
                    if (i === 0 && j < firstDay) {
                        row += "<td></td>";
                    } else if (dayCounter <= daysInMonth) {
                        row += `<td id="entry-${dayCounter}">
                                    <div class="day-header">${dayCounter}</div>
                                </td>`;
                        dayCounter++;
                    } else {
                        row += "<td></td>";
                    }
                }
                row += "</tr>";
                calendarBody.innerHTML += row;
                if (dayCounter > daysInMonth) break;
            }
            document.getElementById("currentMonthYear").textContent =
                new Date(year, month).toLocaleString('default', {
                    month: 'long',
                    year: 'numeric'
                });
        }

        function formatTime(time) {
            let [hour, minute] = time.split(":").map(Number);
            let period = hour >= 12 ? "PM" : "AM";
            hour = hour % 12 || 12;
            return `${hour}:${String(minute).padStart(2, '0')} ${period}`;
        }

        function setTime(type) {
            const selectedDate = new Date(document.getElementById("datePicker").value);
            const day = selectedDate.getDate();

            if (isNaN(day)) {
                alert("Please select a valid date.");
                return;
            }

            let entry = document.getElementById(`entry-${day}`);
            if (!entry) return;

            let timeValue = "";
            let entryType = "";

            switch (type) {
                case "AM_IN":
                    timeValue = formatTime(document.getElementById("timeInAM").value);
                    entryType = "AM_IN";
                    break;
                case "AM_OUT":
                    timeValue = formatTime(document.getElementById("timeOutAM").value);
                    entryType = "AM_OUT";
                    break;
                case "PM_IN":
                    timeValue = formatTime(document.getElementById("timeInPM").value);
                    entryType = "PM_IN";
                    break;
                case "PM_OUT":
                    timeValue = formatTime(document.getElementById("timeOutPM").value);
                    entryType = "PM_OUT";
                    break;
            }

            // Check if this entry type already exists for the selected day
            if (entry.querySelector(`.calendar-entry[data-type="${entryType}"]`)) {
                alert(`You have already entered ${entryType.replace("_", " ")} for this day.`);
                return;
            }

            // Add the entry with a data-type attribute to track
            entry.innerHTML += `<div class="calendar-entry" data-type="${entryType}">${timeValue}</div>`;

            // Update total hours
            calculateTotalHours();
        }



        document.addEventListener("DOMContentLoaded", () => {
            generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
            calculateTotalHours();

            // Set default times
            document.getElementById("timeInAM").value = "08:00";
            document.getElementById("timeOutAM").value = "12:00";
            document.getElementById("timeInPM").value = "12:01";
            document.getElementById("timeOutPM").value = "17:00";
        });

        function changeMonth(step) {
            currentDate.setMonth(currentDate.getMonth() + step);
            generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
            calculateTotalHours();
        }

        function resetAllTimes() {
            const entries = document.querySelectorAll(".calendar-entry");
            entries.forEach(entry => {
                entry.remove(); // Removes the entry from the calendar
            });
        }


        function updateClock() {
            let now = new Date();
            let date = now.toLocaleDateString(undefined, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            let time = now.toLocaleTimeString();
            document.getElementById("realTimeClock").innerHTML = `${date} <br> ${time}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function calculateTotalHours() {
            let totalMinutes = 0;
            let days = document.querySelectorAll(".calendar td");

            days.forEach(day => {
                let entries = Array.from(day.querySelectorAll(".calendar-entry"));
                let times = [];

                // Extract time values
                entries.forEach(entry => {
                    let timeText = entry.textContent.trim();
                    let timeParts = timeText.match(/(\d+):(\d+)\s?(AM|PM)/);
                    if (timeParts) {
                        let hours = parseInt(timeParts[1]);
                        let minutes = parseInt(timeParts[2]);
                        let period = timeParts[3];

                        // Convert to 24-hour format
                        if (period === "PM" && hours !== 12) {
                            hours += 12;
                        } else if (period === "AM" && hours === 12) {
                            hours = 0;
                        }
                        times.push(hours * 60 + minutes); // Convert to total minutes
                    }
                });

                // Ensure we have pairs (time-in and time-out)
                if (times.length % 2 === 0) {
                    for (let i = 0; i < times.length; i += 2) {
                        totalMinutes += (times[i + 1] - times[i]); // Compute interval
                    }
                }
            });

            let totalHours = Math.floor(totalMinutes / 60);
            let remainingMinutes = totalMinutes % 60;
            document.getElementById("totalHours").textContent = `${totalHours} Hours ${remainingMinutes} Minutes`;
        }

        document.addEventListener("DOMContentLoaded", () => {
            generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
            calculateTotalHours();
        });
    </script>
</body>

</html>