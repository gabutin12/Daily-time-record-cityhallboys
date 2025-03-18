<?php
// db_connect.php - Separate database connection file

$host = $_SERVER["sql101.infinityfree.com"];
$username = $_SERVER["if0_38545087"];
$password = $_SERVER["xA13Rc79Tr"];
$database = $_SERVER["if0_38545087_XXX"];

// Create a database connection
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
