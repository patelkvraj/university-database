<?php
include 'config.php';

// init variables
$success_message = '';
$error_message = '';
$available_sections = [];
$registered_courses = [];
$student_id = '';
$student_info = null;
$selected_section = '';

// check if student_id was passed in URL
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
}

// check if form was submitted for search
if (isset($_GET['search_student'])) {
    $student_id = $_POST['student_id'];
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
                                    AND t.semester = s.semester AND t.year = s.year) as enrolled_students
                                    FROM section s
                                    JOIN course c ON s.course_id = s.course_id
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
                            JOIN sections s ON t.course_id = s.course_id AND t.section_id = s.section_id
                                    AND t.semester = s.semester AND t.year = s.year
                            LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                            WHERE t.student_id = '$student_id'
                            ORDER BY t.year DESC, FIELD(t.semester, 'Spring', 'Summer', 'Fall', 'Winter'), t.course_id";
        
        $registered_result = mysqli_query($conn, $registered_sql);

        if (mysqli_num_rows($registed_result) > 0) {
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
                                        AND (t.grade IS NULL OR t.grade NO IN ('F', 'D-', 'D', 'D+'))";
                    
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
            $success_message = "Successfully registered for $course_id - $section_id!";

            // refresh page to show updated sections
            header("Location: student_register.php?student_id=" . $student_id);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    }
}

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
    <div>
        <a href="index.html">Home</a> |
        <a href="student.php">Student Account</a> |
        <a href="student_history.php">Course History</a>
    </div>

    <h1>Student Course Registration</h1>

    <?php if ($success_message): ?>
        <div><strong><?php echo $success_message; ?></strong></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>

    <form method="post" action="">
        <div>
            <label for="student_id">Student ID:</label>
            <input type="text" id="student_id" name="student_id" value="<?php echo $student_id; ?>" required>
        </div>
        <button type="submit" name="search_student">Search</button>
    </form>

    <?php if ($student_info): ?>
        <hr>
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
                                $section_full = ($section['enrolled_students'] >= 15);
                                $section_value = "{$section['course_id']}|{$section['section_id']}|{$section['semester']}|{$section['year']}";
                                $section_display = "{$section['course_id']} - {$section['course_name']} ({$section['section_id']})";
                                if ($section_full) {
                                    $section_display .= "[FULL]";
                                } else {
                                    $section_display .= " [{$section['enrolled_students']}/15 students]";
                                }
                            ?>
                            <option value="<?php echo $section_value; ?>" <?php echo $setion_full ? 'disabled' : ''; ?>>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available_sections as $section): ?>
                        <tr>
                            <td><?php echo $course['course_id']; ?></td>
                            <td><?php echo $course['course_name']; ?></td>
                            <td><?php echo $course['section_id']; ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo $course['instructor_name'] ?? 'TBA'; ?></td>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Currently Registered Courses</h2>
        <?php if (empty($registered_courses)): ?>
            <p>No registered courses found.</p>
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
                    <?php foreach ($registered_courses as $course): ?>
                        <tr>
                            <td><?php echo $course['course_id']; ?></td>
                            <td><?php echo $course['course_name']; ?></td>
                            <td><?php echo $course['section_id']; ?></td>
                            <td><?php echo $course['semester']; ?></td>
                            <td><?php echo $course['year']; ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo $course['instructor_name'] ?? 'TBA'; ?></td>
                            <td><?php echo $course['grade'] ?? 'In Progress'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
