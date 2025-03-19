<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: dtr.php");
    exit();
}

$login_error = "";
$savedUsername = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : "";
$savedPassword = isset($_COOKIE['remember_password']) ? $_COOKIE['remember_password'] : "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    require_once 'db_connect.php';

    $loginUsername = $_POST['loginUsername'];
    $loginPassword = $_POST['loginPassword'];
    $remember = isset($_POST['remember']);

    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $loginUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($loginPassword, $user['password'])) {
            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $loginUsername;
            $_SESSION['id'] = $user['id'];

            if ($remember) {
                setcookie("remember_username", $loginUsername, time() + (86400 * 30), "/");
                setcookie("remember_password", $loginPassword, time() + (86400 * 30), "/");
            } else {
                setcookie("remember_username", "", time() - 3600, "/");
                setcookie("remember_password", "", time() - 3600, "/");
            }

            header("Location: dtr.php");
            exit();
        } else {
            $login_error = "Incorrect username or password.";
        }
    } else {
        $login_error = "Incorrect username or password.";
    }
    $stmt->close();
}

// Include the HTML template
include 'index.html';
