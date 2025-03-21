<?php
session_start();
require_once 'db_connect.php';

$registration_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $registerUsername = $_POST['registerUsername'];
    $registerPassword = $_POST['registerPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($registerPassword !== $confirmPassword) {
        $registration_error = "❌ Passwords do not match. Please try again.";
    } else {
        $hashed_password = password_hash($registerPassword, PASSWORD_DEFAULT);
        $check_sql = "SELECT username FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $registerUsername);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $registration_error = "❌ Username already exists. Choose a different one.";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            $insert_sql = "INSERT INTO users (username, password) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ss", $registerUsername, $hashed_password);

            if ($insert_stmt->execute()) {
                $_SESSION['registration_success'] = true;
                header("Location: index.php");
                exit();
            } else {
                $registration_error = "❌ Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .error-message {
            background: #f8d7da;
            color: #a94442;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #f5c6cb;
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

        .btn-success {
            background: #28a745;
            border: none;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center">Register</h2>

            <?php if (!empty($registration_error)) : ?>
                <div class="error-message"><?= $registration_error; ?></div>
            <?php endif; ?>
            <br>

            <form method="post">
                <div class="mb-3">
                    <label for="registerUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="registerUsername" name="registerUsername" required>
                </div>
                <div class="mb-3">
                    <label for="registerPassword" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="registerPassword" name="registerPassword" required>
                        <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('registerPassword')">
                            <i class="fas fa-eye" id="toggleRegisterPassword"></i>
                        </span>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        <span class="input-group-text password-toggle" onclick="togglePasswordVisibility('confirmPassword')">
                            <i class="fas fa-eye toggleRegisterPassword"></i>
                        </span>
                    </div>
                </div>
                <script>
                function togglePasswordVisibility(inputId) {
                    const passwordInput = document.getElementById(inputId);
                    const toggleIcon = document.getElementById('toggle' + inputId.charAt(0).toUpperCase() + inputId.slice(1));
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleIcon.classList.remove('fa-eye');
                        toggleIcon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        toggleIcon.classList.remove('fa-eye-slash');
                        toggleIcon.classList.add('fa-eye');
                    }
                }
                </script>
                <button type="submit" class="btn btn-success w-100" name="register">Register</button>
            </form>

            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-secondary w-100">Switch to Login</a>
            </div>
        </div>
    </div>
</body>

</html>