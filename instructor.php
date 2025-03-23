<?php
// start session
session_start();

// check if user is logged in as instructor
if (!isset($_SESSION['logged_in']) || $_SESSION['account_type'] != 'instructor') {
    // if not already logged in through session, allow login form
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['action']) || $_POST['action'] != 'login'){
        // not logged in and not trying to log in - redirect home
        if (!isset($_GET['instructor_id'])) {
            header("Location: index.html");
            exit();
        }
    }
}

include 'config.php';

// init variables
$page_title = "Instructor Dashboard";
$success_message = '';
$error_message = '';
$instructor_id = '';
$instructor_info = null;
$current_sections = [];
$past_sections = [];
$section_students = [];
$selected_section = '';

// get instructor_id from session or URL
if (isset($_SESSION['instructor_id'])) {
    $instructor_id = $_SESSION['instructor_id'];
} else if (isset($_GET['instructor_id'])) {
    $instructor_id = $_GET['instructor_id'];
    // store in session for future use
    $_SESSION['instructor_id'] = $instructor_id;
}

// check if form was submitted for login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // check creds in account table
    $login_sql = "SELECT * FROM account WHERE email = '$email' AND password = '$password' AND type = 'instructor'";
    $login_result = mysqli_query($conn, $login_sql);

    if (mysqli_num_rows($login_result) > 0) {
        // get account and set session
        $account = mysqli_fetch_assoc($login_result);
        $_SESSION['logged_in'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['account_type'] = 'instructor';

        // get instructor ID
        $instructor_sql = "SELECT instructor_id FROM instructor WHERE email = '$email'";
        $instructor_result = mysqli_query($conn, $instructor_sql);

        if (mysqli_num_rows($instructor_result) > 0) {
            $instructor = mysqli_fetch_assoc($instructor_result);
            $instructor_id = $instructor['instructor_id'];
            $_SESSION['instructor_id'] = $instructor_id;
        } else {
            $error_message = "Instructor record not found.";
        }
    } else {
        $error_message = "Invalid email or password.";
    }
}
    

// if instructor_id available, fetch instructor info
if (!empty($instructor_id)) {
    // fetch instructor info
    $instructor_sql = "SELECT * FROM instructor WHERE instructor_id = '$instructor_id'";
    $instructor_result = mysqli_query($conn, $instructor_sql);

    if (mysqli_num_rows($instructor_result) > 0) {
        $instructor_info = mysqli_fetch_assoc($instructor_result);

        // fetch current sections (Spring 2025)
        $current_sections_sql = "SELECT s.*, c.course_name, cl.building, cl.room_number,
                                    ts.day, ts.start_time, ts.end_time,
                                    (SELECT COUNT(*) FROM take t
                                    WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                                    AND t.semester = s.semester AND t.year = s.year) as enrolled_students,
                                    (SELECT COUNT(*) FROM TA ta
                                    WHERE ta.course_id = s.course_id AND ta.section_id = s.section_id
                                    AND ta.semester = s.semester AND ta.year = s.year) as has_ta
                            FROM section s
                            JOIN course c ON s.course_id = c.course_id
                            LEFT JOIN classroom cl ON s.classroom_id = cl.classroom_id
                            LEFT JOIN time_slot ts ON s.time_slot_id = ts.time_slot_id
                            WHERE s.instructor_id = '$instructor_id'
                            AND s.semester = 'Spring' AND s.year = 2025
                            ORDER BY s.course_id, s.section_id";
        
        $current_result = mysqli_query($conn, $current_sections_sql);

        if (mysqli_num_rows($current_result) > 0) {
            while ($row = mysqli_fetch_assoc($current_result)) {
                $current_sections[] = $row;
            }
        }

        // fetch past sections (before Spring 2025)
        $past_sections_sql = "SELECT s.*, c.course_name, cl.building, cl.room_number,
                            ts.day, ts.start_time, ts.end_time,
                            (SELECT COUNT(*) FROM take t
                            WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                            AND t.semester = s.semester AND t.year = s.year) as enrolled_students
                        FROM section s
                        JOIN course c ON s.course_id = c.course_id
                        LEFT JOIN classroom cl ON s.classroom_id = cl.classroom_id
                        LEFT JOIN time_slot ts ON s.time_slot_id = ts.time_slot_id
                        WHERE s.instructor_id = '$instructor_id'
                        AND (s.year < 2025 OR (s.year = 2025 AND s.semester != 'Spring'))
                        ORDER BY s.year DESC, FIELD(s.semester, 'Spring', 'Summer', 'Fall', 'Winter'), s.course_id";

        $past_result = mysqli_query($conn, $past_sections_sql);

        if (mysqli_num_rows($past_result) > 0) {
            while ($row = mysqli_fetch_assoc($past_result)) {
                $past_sections[] = $row;
            }
        }

        // if specific section is selected, fetch students
        if (isset($_GET['section'])) {
            $selected_section = $_GET['section'];
            list($course_id, $section_id, $semester, $year) = explode('|', $selected_section);

            // fetch students for this section
            $students_sql = "SELECT t.*, s.name, s.email,
                            CASE
                                WHEN u.student_id IS NOT NULL THEN 'undergraduate'
                                WHEN m.student_id IS NOT NULL THEN 'master'
                                WHEN p.student_id IS NOT NULL THEN 'phd'
                                ELSE NULL
                            END as student_type
                        FROM take t
                        JOIN student s ON t.student_id = s.student_id
                        LEFT JOIN undergraduate u ON t.student_id = u.student_id
                        LEFT JOIN master m ON t.student_id = m.student_id
                        LEFT JOIN PhD p ON t.student_id = p.student_id
                        WHERE t.course_id = '$course_id' AND t.section_id = '$section_id'
                        AND t.semester = '$semester' AND t.year = $year
                        ORDER BY s.name";
            
            $students_result = mysqli_query($conn, $students_sql);

            if (mysqli_num_rows($students_result) > 0) {
                while ($row = mysqli_fetch_assoc($students_result)) {
                    $section_students[] = $row;
                }
            }
        }
    } else {
        $error_message = "Instructor not found.";
    }
}
include 'header.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
</head>
<body>
    <h1>Instructor Dashboard</h1>

    <?php if ($success_message): ?>
        <div><strong><?php echo $success_message; ?></strong></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>

    <?php if (!$instructor_info): ?>
        <!-- Instructor Login Form -->
        <h2>Instructor Login</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="login">
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    <?php else: ?>
        <!-- Instructor Information -->
        <div>
            <h2>Instructor Information</h2>
            <p><strong>Name:</strong> <?php echo $instructor_info['instructor_name'];?></p>
            <p><strong>ID:</strong> <?php echo $instructor_info['instructor_id'];?></p>
            <p><strong>Department:</strong> <?php echo $instructor_info['dept_name'];?></p>
            <p><strong>Title:</strong> <?php echo $instructor_info['title'];?></p>
        </div>

        <!-- Current Sections (Spring 2025) -->
        <h2>Current Sections (Spring 2025)</h2>
        <?php if (empty($current_sections)): ?>
            <p>You are not teaching any sections this semester.</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Schedule</th>
                        <th>Location</th>
                        <th>Enrollment</th>
                        <th>TA Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_sections as $section): ?>
                        <tr>
                            <td><?php echo $section['course_id'] . ': ' . $section['course_name'];?></td>
                            <td><?php echo $section['section_id']; ?></td>
                            <td>
                                <?php
                                    if ($section['day'] && $section['start_time'] && $section['end_time']) {
                                        echo "{$section['day']} " . date("g:i A", strtotime($section['start_time'])) . ' - ' . date("g:i A", strtotime($section['end_time']));
                                    } else {
                                        echo "Not Scheduled";
                                    }
                                ?>
                            </td>
                            <td>
                                <?php
                                    if ($section['building'] && $section['room_number']) {
                                        echo "{$section['building']} {$section['room_number']}";
                                    } else {
                                        echo "Not Assigned";
                                    }
                                ?>
                            </td>
                            <td><?php echo $section['enrolled_students']; ?></td>
                            <td>
                                <?php
                                    if ($section['has_ta'] > 0) {
                                        echo "TA Assigned";
                                    } else if ($section['enrolled_students'] > 10) {
                                        echo "TA Needed";
                                    } else {
                                        echo "No TA Required";
                                    }
                                ?>
                            </td>
                            <td>
                                <a href="instructor.php?instructor_id=<?php echo $instructor_id; ?>&section=<?php echo $section['course_id'].'|'.$section['section_id'].'|'.$section['semester'].'|'.$section['year']; ?>">
                                    View Students
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Past Sections -->
        <h2>Past Teaching History</h2>
        <?php if (empty($past_sections)): ?>
            <p>No past teaching record found.</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Year</th>
                        <th>Schedule</th>
                        <th>Location</th>
                        <th>Enrollment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($past_sections as $section): ?>
                        <tr>
                            <td><?php echo $section['course_id'] . ': ' . $section['course_name'];?></td>
                            <td><?php echo $section['section_id']; ?></td>
                            <td><?php echo $section['semester']; ?></td>
                            <td><?php echo $section['year']; ?></td>
                            <td>
                                <?php
                                    if ($section['day'] && $section['start_time'] && $section['end_time']) {
                                        echo "{$section['day']} " . date("g:i A", strtotime($section['start_time'])) . ' - ' . date("g:i A", strtotime($section['end_time']));
                                    } else {
                                        echo "Not Scheduled";
                                    }
                                ?>
                            </td>
                            <td>
                                <?php
                                    if ($section['building'] && $section['room_number']) {
                                        echo "{$section['building']} {$section['room_number']}";
                                    } else {
                                        echo "Not Assigned";
                                    }
                                ?>
                            </td>
                            <td><?php echo $section['enrolled_students']; ?></td>
                            <td>
                                <a href="instructor.php?instructor_id=<?php echo $instructor_id; ?>&section=<?php echo $section['course_id'].'|'.$section['section_id'].'|'.$section['semester'].'|'.$section['year']; ?>">
                                    View Students
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Section Students (if section is selected) -->
        <?php if (!empty($selected_section)): ?>
            <h2>Students in Selected Section</h2>
            <?php if (empty($section_students)): ?>
                <p>No students enrolled in this section.</p>
            <?php else: ?>
                <?php list($course_id, $section_id, $semester, $year) = explode('|', $selected_section); ?>
                <h3><?php echo "$course_id - $section_id ($semester $year)"; ?></h3>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Student Type</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($section_students as $student): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['email']; ?></td>
                                <td><?php echo $student['student_type']; ?></td>
                                <td>
                                    <?php
                                        if ($student['grade']) {
                                            echo '<strong>' . $student['grade'] . '</strong>';
                                        } else {
                                            echo 'In Progress';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>