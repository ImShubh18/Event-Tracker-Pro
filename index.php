<?php
session_start();
include 'db.php';

if(isset($_SESSION['user_id'])) {
    // Redirect based on role
    if($_SESSION['role'] == 'student') {
        header("Location: student_dashboard.php");
    } else {
        header("Location: teacher_dashboard.php");
    }
    exit;
}

// Process login form
if(isset($_POST['login'])) {
    $prn = $_POST['prn'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE prn = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $prn);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['prn'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if($user['role'] == 'student') {
                header("Location: student_dashboard.php");
            } else {
                header("Location: teacher_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventTrackPro - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 400px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative; 
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 20px;
            color: #666;
            cursor: pointer;
            transition: color 0.3s;
            text-decoration: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-btn:hover {
            color: #e74c3c;
            background-color: #f5f5f5;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color:rgb(88, 76, 249);
            font-size: 24px;
            font-weight: 700;
        }

        .login-form h2 {
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }

        .form-control:focus {
            border-color: rgb(88, 76, 249);
            outline: none;
        }

        .btn {
            width: 100%;
            background-color:rgb(88, 76, 249);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #3b5998;
        }

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color:rgb(88, 76, 249);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Added close button -->
        <a href="landing.php" class="close-btn">
            <i class="fas fa-times"></i>
        </a>
        
        <div class="logo">
            <h1>EventTrackPro</h1>
        </div>
        <div class="login-form">
            <h2>Login to Your Account</h2>
            <?php if(isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="PRN Number" name="prn" required>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" placeholder="Password" name="password" required>
                </div>
                <button type="submit" class="btn" name="login">Login</button>
            </form>
            <div class="register-link">
                Don't have an account? <a href="register.php">Register Now</a>
            </div>
        </div>
    </div>
</body>
</html>