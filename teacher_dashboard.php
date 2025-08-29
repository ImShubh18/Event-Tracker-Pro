<?php
// File: teacher_dashboard.php
session_start();
include 'db.php';

// Handle event deletion
if (isset($_GET['delete_event'])) {
    $event_id = $_GET['delete_event'];
    
    // Delete related attendance records first (foreign key constraint)
    $delete_attendance_sql = "DELETE FROM attendance WHERE event_id = ?";
    $delete_attendance_stmt = $conn->prepare($delete_attendance_sql);
    $delete_attendance_stmt->bind_param("i", $event_id);
    $delete_attendance_stmt->execute();
    
    // Now delete the event
    $delete_event_sql = "DELETE FROM events WHERE event_id = ?";
    $delete_event_stmt = $conn->prepare($delete_event_sql);
    $delete_event_stmt->bind_param("i", $event_id);
    
    if ($delete_event_stmt->execute()) {
        header("Location: teacher_dashboard.php?delete_success=1");
        exit;
    } else {
        header("Location: teacher_dashboard.php?delete_error=1");
        exit;
    }
}

// Display delete success/error messages
if (isset($_GET['delete_success'])) {
    $event_message = "Event deleted successfully!";
} else if (isset($_GET['delete_error'])) {
    $event_message = "Failed to delete event: " . $conn->error;
}
// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: index.php");
    exit;
}

// Get teacher info
$prn = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Process add event form
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

// Fetch all events
$events_sql = "SELECT e.*, COUNT(a.student_prn) as attendance_count 
               FROM events e 
               LEFT JOIN attendance a ON e.event_id = a.event_id 
               GROUP BY e.event_id 
               ORDER BY e.event_date DESC";
$events_result = $conn->query($events_sql);

// Handle student search
$student_data = null;
$student_events = null;
if (isset($_GET['search_student']) && !empty($_GET['student_prn'])) {
    $search_prn = $_GET['student_prn'];
    
    // Fetch student details
    $student_sql = "SELECT * FROM users WHERE prn = ? AND role = 'student'";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bind_param("s", $search_prn);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    $student_data = $student_result->fetch_assoc();
    
    if ($student_data) {
        // Fetch all events attended by this student
        $events_sql = "SELECT e.*, a.attended_on 
                       FROM events e 
                       JOIN attendance a ON e.event_id = a.event_id 
                       WHERE a.student_prn = ? 
                       ORDER BY e.event_date DESC";
        $events_stmt = $conn->prepare($events_sql);
        $events_stmt->bind_param("s", $search_prn);
        $events_stmt->execute();
        $student_events = $events_stmt->get_result();
    }
}

// Handle event view
if (isset($_GET['view_event'])) {
    $event_id = $_GET['view_event'];

    // Fetch event details
    $event_sql = "SELECT * FROM events WHERE event_id = ?";
    $event_stmt = $conn->prepare($event_sql);
    $event_stmt->bind_param("i", $event_id);
    $event_stmt->execute();
    $event_result = $event_stmt->get_result();
    $event = $event_result->fetch_assoc();

    // Fetch attendance records
    $attendance_sql = "SELECT a.*, u.name 
                      FROM attendance a 
                      JOIN users u ON a.student_prn = u.prn 
                      WHERE a.event_id = ? 
                      ORDER BY a.attended_on DESC";
    $attendance_stmt = $conn->prepare($attendance_sql);
    $attendance_stmt->bind_param("i", $event_id);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
}

// Handle date-based query from calendar
if (isset($_GET['date'])) {
    $selected_date = $_GET['date'];

    // Get all events on that date
    $event_stmt = $conn->prepare("SELECT * FROM events WHERE event_date = ?");
    $event_stmt->bind_param("s", $selected_date);
    $event_stmt->execute();
    $events_on_date = $event_stmt->get_result();

    $events_with_attendance = [];
    while ($event_row = $events_on_date->fetch_assoc()) {
        // For each event, get attendance
        $att_stmt = $conn->prepare("
            SELECT a.*, u.name FROM attendance a
            JOIN users u ON a.student_prn = u.prn
            WHERE a.event_id = ?
        ");
        $att_stmt->bind_param("i", $event_row['event_id']);
        $att_stmt->execute();
        $att_result = $att_stmt->get_result();

        $events_with_attendance[] = [
            'event' => $event_row,
            'attendees' => $att_result->fetch_all(MYSQLI_ASSOC)
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventTrackPro - Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- FullCalendar CSS - Updated to latest version -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
    <!-- jQuery - Updated to latest version -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- FullCalendar JS - Updated to latest version -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

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
            text-decoration: none;
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
            grid-template-columns: 1fr;
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

        .add-btn, .search-btn {
            background-color: rgb(88, 76, 249);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .event-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .event-list th,
        .event-list td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .event-list th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .event-list tr:hover {
            background-color: #f1f1f1;
        }

        .view-btn {
            background-color: rgb(88, 76, 249);
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .back-btn {
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
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

        .attendance-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .attendance-list th,
        .attendance-list td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .attendance-list th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .attendance-list tr:hover {
            background-color: #f1f1f1;
        }

        .no-records {
            text-align: center;
            color: #666;
            margin-top: 20px;
            padding: 15px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .student-info-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solidrgb(88, 76, 249);
        }

        .student-info-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .student-info-value {
            font-size: 16px;
            color: #333;
        }

        .stat-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-item {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: rgb(88, 76, 249);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .add-event-form, .search-form {
                grid-template-columns: 1fr;
            }
        }

        #calendar {
            max-width: 100%;
            margin-top: 20px;
            height: 600px; /* Set a specific height for calendar */
            background-color: white;
            padding: 10px;
            border-radius: 5px;
        }
        
        /* Calendar styles */
        .fc-day-today {
            background-color: #f0f8ff !important;
        }
        
        .fc-event {
            background-color: rgb(88, 76, 249);
            border-color: #3456a3;
            cursor: pointer;
        }
        
        .fc-toolbar-title {
            font-size: 20px !important;
            color: rgb(88, 76, 249);
        }
        
        .fc-button {
            background-color:rgb(88, 76, 249) !important;
            border-color:rgb(88, 76, 249) !important;
        }
        
        .fc-button:hover {
            background-color: #3456a3 !important;
        }
        
        .fc-col-header-cell {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .delete-btn {
    background-color: #dc3545;
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
    margin-left: 5px;
}

.delete-btn:hover {
    background-color: #c82333;
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
        <h2 class="dashboard-title">Teacher Dashboard</h2>

        <?php if (isset($event_message)): ?>
            <div class="message <?php echo strpos($event_message, 'successfully') !== false ? 'success-message' : 'error-message'; ?>">
                <?php echo $event_message; ?>
            </div>
        <?php endif; ?>

        <!-- Student Search Section -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-search"></i> Student Search
            </div>
            <form action="" method="get" class="search-form">
                <input type="text" name="student_prn" class="form-control" placeholder="Enter Student PRN" required>
                <button type="submit" name="search_student" class="search-btn">Search</button>
            </form>
            
            <?php if (isset($_GET['search_student'])): ?>
                <?php if ($student_data): ?>
                    <div class="card-title">
                        <i class="fas fa-user-graduate"></i> Student Information
                    </div>
                    <div class="student-info">
                        <div class="student-info-item">
                            <div class="student-info-label">Name</div>
                            <div class="student-info-value"><?php echo $student_data['name']; ?></div>
                        </div>
                        <div class="student-info-item">
                            <div class="student-info-label">PRN</div>
                            <div class="student-info-value"><?php echo $student_data['prn']; ?></div>
                        </div>
                        <?php if (isset($student_data['email'])): ?>
                        <div class="student-info-item">
                            <div class="student-info-label">Email</div>
                            <div class="student-info-value"><?php echo $student_data['email']; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($student_data['department'])): ?>
                        <div class="student-info-item">
                            <div class="student-info-label">Department</div>
                            <div class="student-info-value"><?php echo $student_data['department']; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-summary">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $student_events ? $student_events->num_rows : 0; ?></div>
                            <div class="stat-label">Total Events Attended</div>
                        </div>
                        <?php 
                        // Calculate attendance percentage if we have total events
                        $total_events_sql = "SELECT COUNT(DISTINCT event_id) as total FROM events";
                        $total_events_result = $conn->query($total_events_sql);
                        $total_events_row = $total_events_result->fetch_assoc();
                        $total_events = $total_events_row['total'];
                        
                        if ($total_events > 0):
                            $attendance_percentage = round(($student_events->num_rows / $total_events) * 100, 1);
                        ?>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                            <div class="stat-label">Attendance Rate</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Get first and last attendance dates
                        if ($student_events && $student_events->num_rows > 0):
                            $student_events->data_seek(0);
                            $first_attendance = null;
                            $last_attendance = null;
                            
                            while ($event = $student_events->fetch_assoc()) {
                                if ($first_attendance === null || strtotime($event['attended_on']) < strtotime($first_attendance)) {
                                    $first_attendance = $event['attended_on'];
                                }
                                if ($last_attendance === null || strtotime($event['attended_on']) > strtotime($last_attendance)) {
                                    $last_attendance = $event['attended_on'];
                                }
                            }
                            $student_events->data_seek(0);
                        ?>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo date('M d', strtotime($last_attendance)); ?></div>
                            <div class="stat-label">Last Attendance</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-title">
                        <i class="fas fa-calendar-check"></i> Attendance History
                    </div>
                    <?php if ($student_events && $student_events->num_rows > 0): ?>
                        <table class="event-list">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Event Date</th>
                                    <th>Attended On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($event = $student_events->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $event['event_name']; ?></td>
                                        <td><?php echo date('F d, Y', strtotime($event['event_date'])); ?></td>
                                        <td><?php echo date('F d, Y h:i A', strtotime($event['attended_on'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-records">No attendance records found for this student.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="message error-message">No student found with PRN: <?php echo htmlspecialchars($_GET['student_prn']); ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Calendar Card -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-calendar"></i> Calendar View
            </div>
            <div id="calendar"></div>
        </div>

        <?php if (isset($_GET['date'])): ?>
            <div class="card">
                <a href="teacher_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <div class="card-title">
                    <i class="fas fa-calendar-day"></i> Events on <?php echo date('F d, Y', strtotime($selected_date)); ?>
                </div>

                <?php if (count($events_with_attendance) > 0): ?>
                    <?php foreach ($events_with_attendance as $e): ?>
                        <div style="margin-top: 15px;">
                            <h4><?php echo $e['event']['event_name']; ?></h4>
                            <p><strong>Attendees:</strong> <?php echo count($e['attendees']); ?></p>

                            <?php if (count($e['attendees']) > 0): ?>
                                <table class="attendance-list">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>PRN</th>
                                            <th>Attendance Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($e['attendees'] as $att): ?>
                                            <tr>
                                                <td><?php echo $att['name']; ?></td>
                                                <td><?php echo $att['student_prn']; ?></td>
                                                <td><?php echo date('F d, Y h:i A', strtotime($att['attended_on'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-records">No attendees found.</div>
                            <?php endif; ?>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-records">No events found for this date.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['view_event'])): ?>
            <!-- Event Details View -->
            <div class="card">
                <a href="teacher_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Events</a>
                <div class="card-title">
                    <i class="fas fa-calendar-check"></i> Event Details: <?php echo $event['event_name']; ?>
                </div>
                <div>
                    <p><strong>Event Date:</strong> <?php echo date('F d, Y', strtotime($event['event_date'])); ?></p>
                    <p><strong>Total Attendees:</strong> <?php echo $attendance_result->num_rows; ?></p>
                </div>

                <div class="card-title" style="margin-top: 20px;">
                    <i class="fas fa-users"></i> Attendance List
                </div>
                <?php if ($attendance_result->num_rows > 0): ?>
                    <table class="attendance-list">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>PRN</th>
                                <th>Attendance Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($attendance = $attendance_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $attendance['name']; ?></td>
                                    <td><?php echo $attendance['student_prn']; ?></td>
                                    <td><?php echo date('F d, Y h:i A', strtotime($attendance['attended_on'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-records">No attendance records found for this event.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Main Dashboard View -->
            <div class="dashboard-content">
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-plus-circle"></i> Add New Event
                    </div>
                    <form action="" method="post" class="add-event-form">
                        <input type="text" name="event_name" class="form-control" placeholder="Event Name" required>
                        <input type="date" name="event_date" class="form-control" required>
                        <button type="submit" name="add_event" class="add-btn">Add Event</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-calendar-alt"></i> All Events
                    </div>
                    <?php if ($events_result->num_rows > 0): ?>
                        <table class="event-list">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Event Date</th>
                                    <th>Attendees</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($event = $events_result->fetch_assoc()): ?>
                                    <tr>
    <td><?php echo $event['event_name']; ?></td>
    <td><?php echo date('F d, Y', strtotime($event['event_date'])); ?></td>
    <td><?php echo $event['attendance_count']; ?></td>
    <td>
        <a href="?view_event=<?php echo $event['event_id']; ?>" class="view-btn">
            <i class="fas fa-eye"></i> View
        </a>
        <a href="?delete_event=<?php echo $event['event_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this event? This will also delete all attendance records for this event.');">
            <i class="fas fa-trash"></i> Delete
        </a>
    </td>
</tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-records">No events have been created yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            // Check if the calendar element exists
            if (!calendarEl) {
                console.error('Calendar element not found');
                return;
            }

            // Create calendar with proper configuration
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 500, // Fixed height
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                dateClick: function(info) {
                    // Redirect to the same page with ?date=YYYY-MM-DD
                    window.location.href = 'teacher_dashboard.php?date=' + info.dateStr;
                },
                eventClick: function(info) {
                    // You could also redirect to view the event
                    var date = info.event.startStr.split('T')[0];
                    window.location.href = 'teacher_dashboard.php?date=' + date;
                },
                events: [
                    <?php
                    // Reset the pointer of the events result
                    if ($events_result) {
                        $events_result->data_seek(0);
                        while ($ev = $events_result->fetch_assoc()) {
                            echo "{ 
                                title: '" . addslashes($ev['event_name']) . "', 
                                date: '" . $ev['event_date'] . "',
                                color: 'rgb(88, 76, 249)'
                            },";
                        }
                    }
                    ?>
                ],
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                // Make today's date highlighted
                dayCellClassNames: function(arg) {
                    if (arg.isPast) {
                        return ['fc-day-past'];
                    } else if (arg.isToday) {
                        return ['fc-day-today'];
                    } else {
                        return ['fc-day-future'];
                    }
                }
            });

            // Render the calendar
            calendar.render();
            
            // Debug info
            console.log('Calendar should be rendered now');
        });
    </script>
</body>

</html>