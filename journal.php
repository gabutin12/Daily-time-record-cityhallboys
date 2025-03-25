<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            display: flex;
            min-height: auto;
            gap: 20px;
            padding: 20px;
            margin: 0 auto;
            max-width: 1800px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            width: 400px;
            min-width: 400px;
            height: auto;
            overflow-y: visible;
            position: sticky;
            top: 20px;
        }

        .page {
            flex: 1;
            background: white;
            min-height: calc(100vh - 40px);
            border-radius: 5px;
            padding: 20px;
        }

        .journal-text {
            min-height: 300px;
            resize: vertical;
        }

        @media print {
            .sidebar {
                display: none;
            }

            .page {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
            }
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
                    <form id="journalForm" class="mt-3">
                        <div class="mb-3">
                            <label for="department" class="form-label">Your Department:</label>
                            <input type="text" class="form-control" id="department" name="department" required>
                        </div>

                        <div class="mb-3">
                            <label for="journalText" class="form-label">Write your Journal/Diary below:</label>
                            <textarea class="form-control journal-text" id="journalText" name="journalText" rows="12" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Submit Journal</button>
                    </form>
                    <a href="dtr.php" class="btn btn-secondary mt-2">Back to DTR</a>
                </div>
            </div>
        </div>

        <div class="page">
            <!-- Blank page content -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Real-time clock function
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const dateString = now.toLocaleDateString();
            document.getElementById('realTimeClock').innerHTML = `${dateString} ${timeString}`;
        }

        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial call

        // Form submission handler
        document.getElementById('journalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Add your form submission logic here
        });
    </script>
</body>

</html>