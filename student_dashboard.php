<?php
// File: student_dashboard.php
session_start();
include 'db.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit;
}

// Get student info
$prn = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Process add event form (New code for adding events)
if (isset($_POST['add_event'])) {
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];

    $insert_sql = "INSERT INTO events (event_name, event_date) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $event_name, $event_date);

    if ($insert_stmt->execute()) {
        $event_message = "Event added successfully!";
    } else {
        $event_message = "Failed to add event: " . $conn->error;
    }
}

// Fetch available events
$events_sql = "SELECT e.*, CASE WHEN a.student_prn IS NOT NULL THEN 1 ELSE 0 END AS has_attended
              FROM events e
              LEFT JOIN attendance a ON e.event_id = a.event_id AND a.student_prn = ?
              ORDER BY e.event_date DESC";
$events_stmt = $conn->prepare($events_sql);
$events_stmt->bind_param("s", $prn);
$events_stmt->execute();
$events_result = $events_stmt->get_result();

// Fetch attended events
$attended_sql = "SELECT e.event_id, e.event_name, e.event_date, a.attended_on 
                FROM events e 
                JOIN attendance a ON e.event_id = a.event_id 
                WHERE a.student_prn = ? 
                ORDER BY a.attended_on DESC";
$attended_stmt = $conn->prepare($attended_sql);
$attended_stmt->bind_param("s", $prn);
$attended_stmt->execute();
$attended_result = $attended_stmt->get_result();

// Handle the attendance marking
if(isset($_POST['mark_attendance'])) {
    $event_id = $_POST['event_id'];
    
    // Check if already marked attendance
    $check_sql = "SELECT * FROM attendance WHERE student_prn = ? AND event_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $prn, $event_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows > 0) {
        $attendance_message = "You have already marked your attendance for this event!";
    } else {
        // Insert attendance record
        $insert_sql = "INSERT INTO attendance (student_prn, event_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("si", $prn, $event_id);
        
        if($insert_stmt->execute()) {
            $attendance_message = "Attendance marked successfully!";
            // Refresh the events lists
            $events_stmt->execute();
            $events_result = $events_stmt->get_result();
            $attended_stmt->execute();
            $attended_result = $attended_stmt->get_result();
        } else {
            $attendance_message = "Failed to mark attendance: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventTrackPro - Student Dashboard</title>
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
            min-height: 100vh;
        }

        .navbar {
            background-color: rgb(88, 76, 249);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .logo h1 {
            font-size: 20px;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
            margin-right: 15px;
        }

        .logout-btn {
            background-color: white;
            color: rgb(88, 76, 249);
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-title {
            text-align: center;
            margin: 20px 0;
            color: #333;
        }

        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: rgb(88, 76, 249);
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 10px;
        }

        .event-list {
            margin-top: 20px;
        }

        .event-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-details h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .event-date {
            font-size: 14px;
            color: #666;
        }

        .mark-attendance-btn {
            background-color:rgb(88, 76, 249);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .mark-attendance-btn:hover {
            background-color: #3b5998;
        }
        
        .marked-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: default;
        }

        .message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            text-align: center;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }

        .attended-status {
            color: #28a745;
            font-weight: 600;
        }

        .no-events {
            text-align: center;
            color: #666;
            margin-top: 20px;
        }

        /* New styles for add event form */
        .add-event-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            align-items: center;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .add-btn {
            background-color:rgb(88, 76, 249);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .add-event-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <h1>EventTrackPro</h1>
        </div>
        <div class="user-info">
            <span><?php echo $name; ?> (<?php echo $prn; ?>)</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2 class="dashboard-title">Student Dashboard</h2>
        
        <?php if(isset($attendance_message) || isset($event_message)): ?>
            <div id="message-container" class="message <?php echo (isset($attendance_message) && strpos($attendance_message, 'successfully') !== false) || (isset($event_message) && strpos($event_message, 'successfully') !== false) ? 'success-message' : 'error-message'; ?>">
                <?php echo isset($attendance_message) ? $attendance_message : $event_message; ?>
            </div>
        <?php endif; ?>

        <!-- New Card: Add Event Form -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-plus-circle"></i> Create New Event
            </div>
            <form action="" method="post" class="add-event-form">
                <input type="text" name="event_name" class="form-control" placeholder="Event Name" required>
                <input type="date" name="event_date" class="form-control" required>
                <button type="submit" name="add_event" class="add-btn">Add Event</button>
            </form>
        </div>

        <div class="dashboard-content">
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-calendar-alt"></i> Available Events
                </div>
                <div class="event-list">
                    <?php if($events_result->num_rows > 0): ?>
                        <?php while($event = $events_result->fetch_assoc()): ?>
                            <div class="event-item">
                                <div class="event-details">
                                    <h3><?php echo $event['event_name']; ?></h3>
                                    <div class="event-date">
                                        <i class="far fa-calendar"></i> <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                                    </div>
                                </div>
                                <?php if($event['has_attended']): ?>
                                    <div class="marked-btn">
                                        <i class="fas fa-check"></i> Marked Attendance
                                    </div>
                                <?php else: ?>
                                    <form action="" method="post">
                                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                        <button type="submit" name="mark_attendance" class="mark-attendance-btn">Mark Attendance</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-events">No events available at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">
                    <i class="fas fa-check-circle"></i> Your Attended Events
                </div>
                <div class="event-list">
                    <?php if($attended_result->num_rows > 0): ?>
                        <?php while($attended = $attended_result->fetch_assoc()): ?>
                            <div class="event-item">
                                <div class="event-details">
                                    <h3><?php echo $attended['event_name']; ?></h3>
                                    <div class="event-date">
                                        <i class="far fa-calendar"></i> <?php echo date('F d, Y', strtotime($attended['event_date'])); ?>
                                    </div>
                                </div>
                                <div class="attended-status">
                                    <i class="fas fa-check"></i> Attended on <?php echo date('M d, Y', strtotime($attended['attended_on'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-events">You haven't attended any events yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Make messages disappear after 10 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                setTimeout(function() {
                    messageContainer.style.transition = 'opacity 1s ease';
                    messageContainer.style.opacity = '0';
                    setTimeout(function() {
                        messageContainer.style.display = 'none';
                    }, 1000);
                }, 10000); // 10 seconds
            }
        });
    </script>
</body>
</html>