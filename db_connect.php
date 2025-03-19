<?php
// db_connect.php - Separate database connection file

$host = "localhost";
$username = "root";
$password = "";
$database = "cityhalldtr";

// Create a database connection
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
