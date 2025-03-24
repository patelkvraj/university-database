<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// start session
session_start();

// check if user is logged in as student
if (!isset($_SESSION['logged_in']) || $_SESSION['account_type'] != 'student') {
    header("Location: index.html");
    exit();
}

include 'config.php';

// init variables
$page_title = "Student Dashboard";
$student_id = '';
$student_info = null;
$current_courses = [];
$upcoming_courses = [];

// check if student_id was passed in URL or POST
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
} else if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    // store in session for future use
    $_SESSION['student_id'] = $student_id;
}

// if student_id is provided, fetch student info
if (!empty($student_id)) {
    // fetch student info
    $student_sql = "SELECT s.*,
                    u.total_credits as undergrad_credits, u.class_standing,
                    m.total_credits as master_credits,
                    CASE
                        WHEN u.student_id IS NOT NULL THEN 'undergraduate'
                        WHEN m.student_id IS NOT NULL THEN 'master'
                        WHEN p.student_id IS NOT NULL THEN 'phd'
                        ELSE NULL
                    END as student_type
                FROM student s
                LEFT JOIN undergraduate u ON s.student_id = u.student_id
                LEFT JOIN master m ON s.student_id = m.student_id
                LEFT JOIN PhD p ON s.student_id = p.student_id
                WHERE s.student_id = '$student_id'";

    $student_result = mysqli_query($conn, $student_sql);
    if (!$student_result) {
        die("Database error: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($student_result) > 0) {
        $student_info = mysqli_fetch_assoc($student_result);

        // fetch current courses (Spring 2025)
        $current_sql = "SELECT t.*, c.course_name, c.credits, i.instructor_name,
                        cl.building, cl.room_number, ts.day, ts.start_time, ts.end_time
                        FROM take t
                        JOIN course c ON t.course_id = c.course_id
                        JOIN section s ON t.course_id = s.course_id AND t.section_id = s.section_id
                                AND t.semester = s.semester AND t.year = s.year
                        LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                        LEFT JOIN classroom cl ON s.classroom_id = cl.classroom_id
                        LEFT JOIN time_slot ts ON s.time_slot_id = ts.time_slot_id
                        WHERE t.student_id = '$student_id'
                        AND t.semester = 'Spring' AND t.year = 2025
                        ORDER BY c.course_id";
        
        $current_result = mysqli_query($conn, $current_sql);
        if (!$current_result) {
            die("Database error: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($current_result) > 0) {
            while ($row = mysqli_fetch_assoc($current_result)) {
                $current_courses[] = $row;
            }
        }

        // fetch available courses for registration
        $available_sql = "SELECT s.course_id, s.section_id, s.semester, s.year, c.course_name, c.credits,
                            i.instructor_name, cl.building, cl.room_number, ts.day, ts.start_time, ts.end_time,
                            (SELECT COUNT(*) FROM take t WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                            AND t.semester = s.semester AND t.year = s.year) as enrolled_students
                            FROM section s
                            JOIN course c ON s.course_id = c.course_id
                            LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                            LEFT JOIN classroom cl ON s.classroom_id = cl.classroom_id
                            LEFT JOIN time_slot ts ON s.time_slot_id = ts.time_slot_id
                            WHERE s.semester = 'Spring' AND s.year = 2025
                            AND NOT EXISTS (
                                SELECT 1 FROM take t
                                WHERE t.student_id = '$student_id'
                                AND t.course_id = s.course_id
                                AND t.section_id = s.section_id
                                AND t.semester = s.semester
                                AND t.year = s.year
                            )
                            AND (SELECT COUNT(*) FROM take t WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                                AND t.semester = s.semester AND t.year = s.year) < 15
                            LIMIT 5";
        $available_result = mysqli_query($conn, $available_sql);
        if (!$available_result) {
            die("Database error: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($available_result) > 0) {
            while ($row = mysqli_fetch_assoc($available_result)) {
                $upcoming_courses[] = $row;
            }
        }
    }
}
// include header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Student Dashboard</h1>

    <?php if (!$student_info): ?>
        <div>
            <strong>Unable to find your student information.</strong>
            <p><a href="logout.php">Please log out and try again</a></p>
        </div>
    <?php else: ?>
        <div>
            <h2>Welcome, <?php echo $student_info['name']; ?>!</h2>
            <p><strong>Student ID:</strong> <?php echo $student_info['student_id'];?></p>
            <p><strong>Department:</strong> <?php echo $student_info['dept_name'];?></p>
            <p><strong>Student Type:</strong> <?php echo $student_info['student_type'];?></p>

            <?php if ($student_info['student_type'] == 'undergraduate'): ?>
                <p><strong>Total Credits:</strong> <?php echo $student_info['undergrad_credits'];?></p>
                <p><strong>Class Standing:</strong> <?php echo $student_info['class_standing'];?></p>
            <?php elseif ($student_info['student_type'] == 'master'): ?>
                <p><strong>Total Credits:</strong> <?php echo $student_info['master_credits'];?></p>
            <?php endif; ?>
        </div>

        <div>
            <h2>Quick Links</h2>
            <ul>
                <li><a href="student.php?student_id=<?php echo $student_id; ?>">Update Account Information</a></li>
                <li><a href="student_register.php?student_id=<?php echo $student_id; ?>">Course Registration</a></li>
                <li><a href="student_history.php?student_id=<?php echo $student_id; ?>">View Complete Course History</a></li>
            </ul>
        </div>

        <div>
            <h2>Current Courses (Spring 2025)</h2>
            <?php if (empty($current_courses)): ?>
                <p>You are not currently enrolled in any courses.</p>
                <p><a href="student_register.php?student_id=<?php echo $student_id; ?>">Register for courses</a></p>
            <?php else: ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Credits</th>
                            <th>Instructor</th>
                            <th>Schedule</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_courses as $course): ?>
                            <tr>
                                <td><?php echo $course['course_id'] . ': ' . $course['course_name']; ?></td>
                                <td><?php echo $course['credits']; ?></td>
                                <td><?php echo $course['instructor_name'] ?? 'Not Assigned'; ?></td>
                                <td>
                                    <?php
                                        if ($course['day'] && $course['start_time'] && $course['end_time']) {
                                            echo "{$course['day']} " . date("g:i A", strtotime($course['start_time'])) . ' - ' . date("g:i A", strtotime($course['end_time']));
                                        } else {
                                            echo "Not Scheduled";
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        if ($course['building'] && $course['room_number']) {
                                            echo "{$course['building']} {$course['room_number']}";
                                        } else {
                                            echo "Not Assigned";
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div>
            <h2>Available Courses for Registration</h2>
            <?php if (empty($upcoming_courses)): ?>
                <p>No available courses found for registration.</p>
            <?php else: ?>
                <p>Here are some available courses you can register for:</p>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Credits</th>
                            <th>Instructor</th>
                            <th>Schedule</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_courses as $course): ?>
                            <tr>
                                <td><?php echo $course['course_id'] . ': ' . $course['course_name']; ?></td>
                                <td><?php echo $course['section_id']; ?></td>
                                <td><?php echo $course['credits']; ?></td>
                                <td><?php echo $course['instructor_name'] ?? 'Not Assigned'; ?></td>
                                <td>
                                    <?php
                                        if ($course['day'] && $course['start_time'] && $course['end_time']) {
                                            echo "{$course['day']} " . date("g:i A", strtotime($course['start_time'])) . ' - ' . date("g:i A", strtotime($course['end_time']));
                                        } else {
                                            echo "Not Scheduled";
                                        }
                                    ?>
                                </td>
                                <td><?php echo $course['enrolled_students']; ?>/15</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><a href="student_register.php?student_id=<?php echo $student_id; ?>">View All Available Courses</a></p>
            <?php endif; ?>
        </div>
        <div>
            <h2>Upcoming Deadlines</h2>
            <?php
            // query to get upcoming deadlines for this student
            $upcoming_sql = "SELECT st.todo_id, st.todo_title, st.due_date, st.is_completed,
                                ce.course_id, c.course_name, ce.event_type
                            FROM student_todo st
                            LEFT JOIN course_event ce ON st.event_id = ce.event_id
                            LEFT JOIN course c ON ce.course_id = c.course_id
                            WHERE st.student_id = '$student_id'
                            AND st.is_completed = 0
                            AND st.due_date >= CURDATE()
                            ORDER BY st.due_date ASC
                            LIMIT 5";
            $upcoming_result = mysqli_query($conn, $upcoming_sql);

            if (mysqli_num_rows($upcoming_result) > 0):
            ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Title</th>
                            <th>Course</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($todo = mysqli_fetch_assoc($upcoming_result)): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($todo['due_date'])); ?></td>
                                <td><?php echo $todo['todo_title']; ?></td>
                                <td>
                                    <?php
                                    if ($todo['course_id']) {
                                        echo $todo['course_id'] . ': ' . $todo['course_name'];
                                    } else {
                                        echo 'Personal';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $todo['event_type'] ? ucfirst($todo['event_type']) : 'Task'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <p><a href="student_todo.php?student_id=<?php echo $student_id; ?>">View all To-Do Items</a></p>
            <?php else: ?>
                <p>No upcoming deadlines found.</p>
                <p><a href="student_todo.php?student_id=<?php echo $student_id; ?>">Add To-Do Items</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>