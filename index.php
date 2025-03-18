<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: dtr.php");
    exit();
}

$login_error = "";

// Remember Me Feature
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

    header("Location: dtr.php"); // ✅ Redirect to dtr.php instead
    exit();
    }
     else {
            $login_error = "Incorrect username or password.";
        }
    } else {
        $login_error = "Incorrect username or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 500px;
        }

        .form-container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease-in-out;
            width: 100%;
        }

        h2 {
            color: #333;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: all 0.3s ease-in-out;
            padding: 12px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0px 0px 8px rgba(102, 126, 234, 0.5);
        }

        .btn {
            border-radius: 8px;
            transition: all 0.3s ease-in-out;
            padding: 12px;
            font-size: 16px;
        }

        .btn-primary {
            background: #667eea;
            border: none;
        }

        .btn-primary:hover {
            background: #5563c1;
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .text-center button {
            margin-top: 10px;
        }

        /* Fade-in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .error-message {
            background: #f8d7da;
            color: #a94442;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center">Login</h2>
            <?php if (!empty($login_error)) : ?>
                <div class="error-message"><?= $login_error; ?></div>
            <?php endif; ?>
            <br>
            <form method="post">
                <div class="mb-3">
                    <label for="loginUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="loginUsername" name="loginUsername" value="<?= htmlspecialchars($savedUsername) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="loginPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="loginPassword" name="loginPassword" value="<?= htmlspecialchars($savedPassword) ?>" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" <?= ($savedUsername ? 'checked' : '') ?>>
                    <label class="form-check-label" for="remember">Remember Me</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100" name="login">Login</button>
            </form>
            <div class="text-center mt-3">
                <a href="register.php" class="btn btn-secondary w-100">Switch to Register</a>
            </div>
        </div>
    </div>
</body>
</html>
