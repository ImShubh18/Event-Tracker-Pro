<?php
// File: register.php
session_start();
include 'db.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    // Redirect based on role
    if($_SESSION['role'] == 'student') {
        header("Location: student_dashboard.php");
    } else {
        header("Location: teacher_dashboard.php");
    }
    exit;
}

// Process registration form
if(isset($_POST['register'])) {
    $prn = $_POST['prn'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Check if passwords match
    if($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if PRN already exists
        $check_sql = "SELECT * FROM users WHERE prn = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $prn);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $error = "PRN already registered!";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_sql = "INSERT INTO users (prn, name, email, password, role) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $prn, $name, $email, $hashed_password, $role);
            
            if($insert_stmt->execute()) {
                // Registration successful
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventTrackPro - Register</title>
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
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative; /* Added for absolute positioning of cross button */
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

        .register-form h2 {
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

        .radio-group {
            display: flex;
            margin-bottom: 15px;
        }

        .radio-option {
            margin-right: 20px;
            display: flex;
            align-items: center;
        }

        .radio-option input {
            margin-right: 5px;
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

        .message {
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
        }

        .error-message {
            color: white;
            background-color: #e74c3c;
        }

        .success-message {
            color: white;
            background-color: #2ecc71;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color:rgb(88, 76, 249);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
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
        <div class="register-form">
            <h2>Create a New Account</h2>
            <?php if(isset($error)): ?>
                <div class="message error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($success)): ?>
                <div class="message success-message">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="PRN Number" name="prn" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Full Name" name="name" required>
                </div>
                <div class="form-group">
                    <input type="email" class="form-control" placeholder="Email Address" name="email" required>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" placeholder="Password" name="password" required>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" placeholder="Confirm Password" name="confirm_password" required>
                </div>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="student" name="role" value="student" checked>
                        <label for="student">Student</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="teacher" name="role" value="teacher">
                        <label for="teacher">Teacher</label>
                    </div>
                </div>
                <button type="submit" class="btn" name="register">Register</button>
            </form>
            <div class="login-link">
                Already have an account? <a href="index.php">Login Now</a>
            </div>
        </div>
    </div>
</body>
</html>