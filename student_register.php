<?php
// start session
session_start();

include 'config.php';

// check if user is logged in as student
if (!isset($_SESSION['logged_in']) || $_SESSION['account_type'] != 'student') {
    header("Location: index.html");
    exit();
}

// init variables
$success_message = '';
$error_message = '';
$available_sections = [];
$registered_courses = [];
$student_id = '';
$student_info = null;
$selected_section = '';

// check if student_id was passed in URL
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
} else if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    // store in session for future use
    $_SESSION['student_id'] = $student_id;
}

// check if form was submitted for search
if (isset($_GET['search_student'])) {
    $student_id = $_GET['student_id'];
    $_SESSION['student_id'] = $student_id;
}

// if student_id available, fetch student info
if (!empty($student_id)) {
    // fetch student info
    $student_sql = "SELECT s.*, u.total_credits as undergrad_credits, u.class_standing,
                    m.total_credits as master_credits,
                    CASE
                        WHEN u.student_id IS NOT NULL THEN 'undergraduate'
                        WHEN m.student_id IS NOT NULL THEN 'master'
                        WHEN p.student_id IS NOT NULL THEN 'phd'
                        ELSE NULL
                    END as student_type
                FROM student s
                LEFT JOIN undergraduate u ON s.student_id = u.student_id
                LEFT JOIN master m on s.student_id = m.student_id
                LEFT JOIN PhD p ON s.student_id = p.student_id
                WHERE s.student_id = '$student_id'";

    $student_result = mysqli_query($conn, $student_sql);

    if (mysqli_num_rows($student_result) > 0) {
        $student_info = mysqli_fetch_assoc($student_result);

        // fetch available sections (not already registered by student)
        $available_sections_sql = "SELECT s.course_id, s.section_id, s.semester, s.year, c.course_name, c.credits,
                                    i.instructor_name, cl.building, cl.room_number, ts.day, ts.start_time, ts.end_time,
                                    (SELECT COUNT(*) FROM take t WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                                    AND t.semester = s.semester AND t.year = s.year) as enrolled_students,
                                    CASE
                                        WHEN s.time_slot_id IS NULL THEN 'No'
                                        WHEN EXISTS (
                                            SELECT 1
                                            FROM take t2
                                            JOIN section s2 ON t2.course_id = s2.course_id
                                                AND t2.section_id = s2.section_id
                                                AND t2.semester = s2.semester
                                                AND t2.year = s2.year
                                            JOIN time_slot ts2 ON s2.time_slot_id = ts2.time_slot_id
                                            WHERE t2.student_id = '$student_id'
                                            AND t2.semester = 'Spring' AND t2.year = 2025
                                            AND s2.time_slot_id IS NOT NULL
                                            AND ts.day = ts2.day
                                            AND (( ts.start_time <= ts2.start_time AND ts.end_time > ts2.start_time)
                                                OR (ts.start_time < ts2.end_time AND ts.end_time >= ts2.end_time)
                                                OR (ts.start_time >= ts2.start_time AND ts.end_time <= ts2.end_time))
                                        ) THEN 'Yes'
                                        ELSE 'No'
                                    END as has_time_conflict
                                    FROM section s
                                    JOIN course c ON s.course_id = c.course_id
                                    LEFT JOIN instructor i on s.instructor_id = i.instructor_id
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
                                    ORDER BY s.course_id, s.section_id";
        
        $available_result = mysqli_query($conn, $available_sections_sql);

        if (mysqli_num_rows($available_result) > 0) {
            while ($row = mysqli_fetch_assoc($available_result)) {
                $available_sections[] = $row;
            }
        }

        // fetch already registered courses
        $registered_sql = "SELECT t.course_id, t.section_id, t.semester, t.year, t.grade,
                            c.course_name, c.credits, i.instructor_name
                            FROM take t
                            JOIN course c ON t.course_id = c.course_id
                            JOIN section s ON t.course_id = s.course_id AND t.section_id = s.section_id
                                    AND t.semester = s.semester AND t.year = s.year
                            LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                            WHERE t.student_id = '$student_id'
                            ORDER BY t.year DESC, FIELD(t.semester, 'Spring', 'Summer', 'Fall', 'Winter'), t.course_id";
        
        $registered_result = mysqli_query($conn, $registered_sql);

        if (mysqli_num_rows($registered_result) > 0) {
            while ($row = mysqli_fetch_assoc($registered_result)) {
                $registered_courses[] = $row;
            }
        }
    } else {
        $error_message = "Student not found.";
    }
}

// check if form was submitted for registration
if (isset($_POST['register'])) {
    $student_id = $_POST['student_id'];
    $section_info = explode('|', $_POST['section']);
    $course_id = $section_info[0];
    $section_id = $section_info[1];
    $semester = $section_info[2];
    $year = $section_info[3];

    // check if student exists
    $student_check = mysqli_query($conn, "SELECT * FROM student WHERE student_id = '$student_id'");
    if (mysqli_num_rows($student_check) == 0) {
        $error_message = "Student not found.";
    } else {
        // start transaction
        mysqli_begin_transaction($conn);

        try {
            // check prereqs
            $prereq_sql = "SELECT p.prereq_id FROM prereq p WHERE p.course_id = '$course_id'";
            $prereq_result = mysqli_query($conn, $prereq_sql);

            $missing_prereqs = [];

            if (mysqli_num_rows($prereq_result) > 0) {
                while ($prereq_row = mysqli_fetch_assoc($prereq_result)) {
                    $prereq_id = $prereq_row['prereq_id'];

                    // check if student has completed prereq
                    $completed_sql = "SELECT * FROM take t
                                        WHERE t.student_id = '$student_id'
                                        AND t.course_id = '$prereq_id'
                                        AND (t.grade IS NULL OR t.grade NOT IN ('F', 'D-', 'D', 'D+'))";
                    
                    $completed_result = mysqli_query($conn, $completed_sql);

                    if (mysqli_num_rows($completed_result) == 0) {
                        // get prereq course name
                        $prereq_name_result = mysqli_query($conn, "SELECT course_name FROM course WHERE course_id = '$prereq_id'");
                        $prereq_name = mysqli_fetch_assoc($prereq_name_result)['course_name'];
                        $missing_prereqs[] = "$prereq_id: $prereq_name";
                    }
                }
            }

            if (!empty($missing_prereqs)) {
                throw new Exception("Missing prerequisites: " . implode(", ", $missing_prereqs));
            }

            // check if student is already enrolled for another section of the same course
            $duplicate_check_sql = "SELECT t.section_id, c.course_name
                                    FROM take t
                                    JOIN course c ON t.course_id = c.course_id
                                    WHERE t.student_id = '$student_id'
                                    AND t.course_id = '$course_id'
                                    AND t.semester = '$semester'
                                    AND t.year = $year";

            $duplicate_result = mysqli_query($conn, $duplicate_check_sql);

            if (mysqli_num_rows($duplicate_result) > 0) {
                $duplicate_section = mysqli_fetch_assoc($duplicate_result);
                $success_message = "You are already enrolled in {$course_id} ({$duplicate_section['course_name']}) section {$duplicate_section['section_id']}. Registration completed for section $section_id.";
            }

            // check for time slot conflicts with already registered courses
            $time_slot_sql = "SELECT ts.time_slot_id, ts.day, ts.start_time, ts.end_time
                            FROM section s
                            JOIN time_slot ts ON s.time_slot_id = ts.time_slot_id
                            WHERE s.course_id = '$course_id'
                            AND s.section_id = '$section_id'
                            AND s.semester = '$semester'
                            AND s.year = $year";
            
            $time_slot_result = mysqli_query($conn, $time_slot_sql);

            if (mysqli_num_rows($time_slot_result) > 0) {
                $time_slot = mysqli_fetch_assoc($time_slot_result);

                // now check if student has any conflicts with existing courses
                if (!empty($time_slot['time_slot_id'])) {
                    $conflict_sql = "SELECT t.course_id, t.section_id, c.course_name, ts.day,
                                    DATE_FORMAT(ts.start_time, '%h:%i %p') as start_time,
                                    DATE_FORMAT(ts.end_time, '%h:%i %p') as end_time
                                    FROM take t
                                    JOIN section s ON t.course_id = s.course_id
                                        AND t.section_id = s.section_id
                                        AND t.semester = s.semester
                                        AND t.year = s.year
                                    JOIN time_slot ts ON s.time_slot_id = ts.time_slot_id
                                    JOIN course c ON t.course_id = c.course_id
                                    WHERE t.student_id = '$student_id'
                                    AND t.semester = '$semester'
                                    AND t.year = $year
                                    AND ts.day = '{$time_slot['day']}'
                                    AND (( ts.start_time <= '{$time_slot['start_time']}' AND ts.end_time > '{$time_slot['start_time']}')
                                        OR ( ts.start_time < '{$time_slot['end_time']}' AND ts.end_time >= '{$time_slot['end_time']}')
                                        OR ( ts.start_time >= '{$time_slot['start_time']}' AND ts.end_time <= '{$time_slot['end_time']}'))";
                    
                    $conflict_result = mysqli_query($conn, $conflict_sql);

                    if (mysqli_num_rows($conflict_result) > 0) {
                        $conflict_course = mysqli_fetch_assoc($conflict_result);
                        throw new Exception("Time conflict with {$conflict_course['course_id']} ({$conflict_course['course_name']}) on {$conflict_course['day']} at {$conflict_course['start_time']} - {$conflict_course['end_time']}");
                    }
                }
            }

            // check section capacity
            $enrolled_sql = "SELECT COUNT(*) as count FROM take
                            WHERE course_id = '$course_id' AND section_id = '$section_id'
                            AND semester = '$semester' AND year = $year";
            
            $enrolled_result = mysqli_query($conn, $enrolled_sql);
            $enrolled_count = mysqli_fetch_assoc($enrolled_result)['count'];

            if ($enrolled_count >= 15) {
                throw new Exception("Section is full (maximum 15 students per section).");
            }

            // register student for the course
            $register_sql = "INSERT INTO take (student_id, course_id, section_id, semester, year)
                            VALUES ('$student_id', '$course_id', '$section_id', '$semester', $year)";

            if (!mysqli_query($conn, $register_sql)) {
                throw new Exception("Error registering for course: " . mysqli_error($conn));
            }

            // check if section has more than 10 students and needs TA
            if ($enrolled_count + 1 > 10) {
                // check if TA is already assigned
                $ta_check_sql = "SELECT * FROM TA
                                WHERE course_id = '$course_id' AND section_id = '$section_id'
                                AND semester = '$semester' AND year = $year";
                
                $ta_check_result = mysqli_query($conn, $ta_check_sql);

                if (mysqli_num_rows($ta_check_result) == 0) {
                    // find eligible PhD student who isnt already TA
                    $phd_sql = "SELECT p.student_id FROM PhD p
                                JOIN student s ON p.student_id = s.student_id
                                WHERE NOT EXISTS (
                                    SELECT 1 FROM TA
                                    WHERE TA.student_id = p.student_id
                                    AND TA.semester = '$semester' AND TA.year = $year
                                )
                                LIMIT 1";
                    
                    $phd_result = mysqli_query($conn, $phd_sql);

                    if (mysqli_num_rows($phd_result) > 0) {
                        $phd_student = mysqli_fetch_assoc($phd_result)['student_id'];

                        // assign PhD student as TA
                        $assign_ta_sql = "INSERT INTO TA (student_id, course_id, section_id, semester, year)
                                        VALUES ('$phd_student', '$course_id', '$section_id', '$semester', $year)";
                        
                        if (!mysqli_query($conn, $assign_ta_sql)) {
                            // log error
                            error_log("Error assigning TA: " . mysqli_error($conn));
                        }
                    }
                }
            }

            // commit transaction
            mysqli_commit($conn);

            if (empty($success_message)) {
                $success_message = "Successfully registered for $course_id - $section_id!";
            }

            // refresh page to show updated sections
            header("Location: student_register.php?student_id=" . $student_id);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    }
}
// include header
include 'header.php';

// *********************
// END OF PHP LOGIC (mostly)
// *********************
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Course Registration</title>
</head>
<body>
    <h1>Student Course Registration</h1>

    <?php if ($success_message): ?>
        <div><strong><?php echo $success_message; ?></strong></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>

    <form method="get" action="">
        <div>
            <label for="student_id">Student ID:</label>
            <input type="text" id="student_id" name="student_id" value="<?php echo $student_id; ?>" required>
        </div>
        <button type="submit" name="search_student">Search</button>
    </form>

    <?php if (!$student_info): ?>
        <div>
            <strong>Unable to find your student information.</strong>
            <p><a href="logout.php">Please log out and try again</a></p>
        </div>
    <?php else: ?>
        <div>
            <h3>Student Information</h3>
            <p><strong>Name:</strong> <?php echo $student_info['name']; ?></p>
            <p><strong>ID:</strong> <?php echo $student_info['student_id']; ?></p>
            <p><strong>Department:</strong> <?php echo $student_info['dept_name']; ?></p>
            <p><strong>Student Type:</strong> <?php echo ucfirst($student_info['student_type']); ?></p>
            <?php if ($student_info['student_type'] == 'undergraduate'): ?>
                <p><strong>Total Credits:</strong> <?php echo $student_info['undergrad_credits']; ?></p>
                <p><strong>Class Standing:</strong> <?php echo $student_info['class_standing']; ?></p>
            <?php elseif ($student_info['student_type'] == 'master'): ?>
                <p><strong>Total Credits:</strong> <?php echo $student_info['master_credits']; ?></p>
            <?php endif; ?>
        </div>

        <h2>Available Sections (Spring 2025)</h2>
        <?php if (empty($available_sections)): ?>
            <p>No available sections found for registration.</p>
        <?php else: ?>
            <form method="post" action="">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                <div>
                    <label for="section">Select Section:</label>
                    <select id="section" name="section" required>
                        <option value="">-- Select a section --</option>
                        <?php foreach ($available_sections as $section): ?>
                            <?php
                                // check if student is already registered for this course (different section)
                                $already_registered = false;
                                $course_id = $section['course_id'];
                                foreach ($registered_courses as $reg_course) {
                                    if ($reg_course['course_id'] == $course_id &&
                                        $reg_course['semester'] == 'Spring' &&
                                        $reg_course['year'] == 2025) {
                                        $already_registered = true;
                                        break;
                                    }
                                }

                                $section_full = ($section['enrolled_students'] >= 15);
                                $has_time_conflict = ($section['has_time_conflict'] == 'Yes');
                                $section_value = "{$section['course_id']}|{$section['section_id']}|{$section['semester']}|{$section['year']}";
                                $section_display = "{$section['course_id']} - {$section['course_name']} ({$section['section_id']})";

                                if ($already_registered) {
                                    $section_display .= " [ALREADY REGISTERED IN DIFFERENT SECTION]";
                                }
                                if ($section_full) {
                                    $section_display .= "[FULL]";
                                } else {
                                    $section_display .= " [{$section['enrolled_students']}/15 students]";
                                }
                                if ($has_time_conflict) {
                                    $section_display .= " [TIME CONFLICT]";
                                }
                                $disabled = ($section_full || $has_time_conflict) ? 'disabled' : '';
                            ?>
                            <option value="<?php echo $section_value; ?>" <?php echo $disabled; ?>>
                                <?php echo $section_display; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="register">Register for Course</button>
            </form>

            <h3>Section Details</h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Section</th>
                        <th>Credits</th>
                        <th>Instructor</th>
                        <th>Schedule</th>
                        <th>Location</th>
                        <th>Enrollment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available_sections as $section): ?>
                        <?php
                            // check if student is already registered for this course (different section)
                            $already_registered = false;
                            $course_id = $section['course_id'];
                            foreach ($registered_courses as $reg_course) {
                                if ($reg_course['course_id'] == $course_id &&
                                    $reg_course['semester'] == 'Spring' &&
                                    $reg_course['year'] == 2025) {
                                    $already_registered = true;
                                    break;
                                }
                            }
                        ?>
                        <tr>
                            <td><?php echo $section['course_id']; ?></td>
                            <td><?php echo $section['course_name']; ?></td>
                            <td><?php echo $section['section_id']; ?></td>
                            <td><?php echo $section['credits']; ?></td>
                            <td><?php echo $section['instructor_name'] ?? 'TBA'; ?></td>
                            <td>
                                <?php
                                    if ($section['day'] && $section['start_time'] && $section['end_time']) {
                                        echo "{$section['day']} " . date("g:i A", strtotime($section['start_time'])) . " - " . date("g:i A", strtotime($section['end_time']));
                                    } else {
                                        echo "TBA";
                                    }
                                ?>
                            </td>
                            <td>
                                <?php
                                    if ($section['building'] && $section['room_number']) {
                                        echo "{$section['building']} {$section['room_number']}";
                                    } else {
                                        echo "TBA";
                                    }
                                ?>
                            </td>
                            <td><?php echo $section['enrolled_students']; ?>/15</td>
                            <td>
                                <?php
                                    if ($already_registered) {
                                        echo "<strong style='color:orange'>ALREADY ENROLLED</strong>";
                                    } else if ($section['enrolled_students'] >= 15) {
                                        echo "<strong style='color:red'>FULL</strong>";
                                    } else if ($section['has_time_conflict'] == 'Yes') {
                                        echo "<strong style='color:red'>TIME CONFLICT</strong>";
                                    } else {
                                        echo "<strong style='color:green'>AVAILABLE</strong>";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Current Semester Courses (Spring 2025)</h2>
        <?php
        $current_courses = array_filter($registered_courses, function($course) {
            return $course['semester'] == 'Spring' && $course['year'] == 2025;
        });

        if (empty($current_courses)): 
        ?>
            <p>You are not enrolled in any courses for the current semester..</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Section</th>
                        <th>Credits</th>
                        <th>Instructor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_courses as $course): ?>
                        <tr>
                            <td><?php echo $course['course_id']; ?></td>
                            <td><?php echo $course['course_name']; ?></td>
                            <td><?php echo $course['section_id']; ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo $course['instructor_name'] ?? 'TBA'; ?></td>
                            <td>In Progress</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Past Courses</h2>
        <?php
        $past_courses = array_filter($registered_courses, function($course) {
            return !($course['semester'] == 'Spring' && $course['year'] == 2025);
        });

        if (empty($past_courses)):
        ?>
            <p>No past course history found.</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Year</th>
                        <th>Credits</th>
                        <th>Instructor</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($past_courses as $course): ?>
                        <tr>
                            <td><?php echo $course['course_id']; ?></td>
                            <td><?php echo $course['course_name']; ?></td>
                            <td><?php echo $course['section_id']; ?></td>
                            <td><?php echo $course['semester']; ?></td>
                            <td><?php echo $course['year']; ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo $course['instructor_name'] ?? 'TBA'; ?></td>
                            <td>
                                <?php
                                    if (isset($course['grade']) && $course['grade']) {
                                        echo '<strong>' . $course['grade'] . '</strong>';
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
</body>
</html>
