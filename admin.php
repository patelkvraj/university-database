<?php
// start session
session_start();

include 'config.php';

// check if user is logged in and is admin
$is_admin = false;

// check if logged in as admin
if (isset($_SESSION['logged_in']) && $_SESSION['account_type'] == 'admin') {
    $is_admin = true;
} else {
    $is_admin = false;
}

// init variables
$success_message = '';
$error_message = '';
$instructors = [];
$courses = [];
$time_slots = [];
$classrooms = [];
$sections = [];

// fetch instructors
$instructor_sql =   "SELECT * FROM instructor ORDER BY instructor_name";
$instructor_result = mysqli_query($conn, $instructor_sql);

while($row = mysqli_fetch_assoc($instructor_result)) {
    $instructors[] = $row;
}

// fetch courses
$course_sql =   "SELECT * FROM course ORDER BY course_id";
$course_result = mysqli_query($conn, $course_sql);

while($row = mysqli_fetch_assoc($course_result)) {
    $courses[] = $row;
}

// fetch time slots
$time_slot_sql =   "SELECT * FROM time_slot ORDER BY day, start_time";
$time_slot_result = mysqli_query($conn, $time_slot_sql);

while($row = mysqli_fetch_assoc($time_slot_result)) {
    $time_slots[] = $row;
}

// fetch classrooms
$classroom_sql =   "SELECT * FROM classroom ORDER BY building, room_number";
$classroom_result = mysqli_query($conn, $classroom_sql);

while($row = mysqli_fetch_assoc($classroom_result)) {
    $classrooms[] = $row;
}

// fetch existing sections for Spring 2025
$section_sql = "SELECT s.*, c.course_name, i.instructor_name, cl.building, cl.room_number, ts.day, ts.start_time, ts.end_time, (SELECT COUNT(*) FROM take t WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                    AND t.semester = s.semester AND t.year = s.year) as enrolled_students
                FROM section s
                JOIN course c ON s.course_id = c.course_id
                LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                LEFT JOIN classroom cl ON s.classroom_id = cl.classroom_id
                LEFT JOIN time_slot ts ON s.time_slot_id = ts.time_slot_id
                WHERE s.semester = 'Spring' AND s.year = 2025
                ORDER BY s.course_id, s.section_id";
$section_result = mysqli_query($conn, $section_sql);
while ($row = mysqli_fetch_assoc($section_result)) {
    $sections[] = $row;
}

// handle form submission for adding/updating section
 if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_section') {
        $course_id = $_POST['course_id'];
        $section_id = $_POST['section_id'];
        $instructor_id = isset($_POST['instructor_id']) && !empty($_POST['instructor_id']) ? $_POST['instructor_id'] : null;
        $classroom_id = isset($_POST['classroom_id']) && !empty($_POST['classroom_id']) ? $_POST['classroom_id'] : null;
        $time_slot_id = isset($_POST['time_slot_id']) && !empty($_POST['time_slot_id']) ? $_POST['time_slot_id'] : null;

        // start transaction
        mysqli_begin_transaction($conn);

        try {
            // check if section already exists
            $check_sql = "SELECT * FROM section WHERE course_id = '$course_id' AND section_id = '$section_id'
                            AND semester = 'Spring' AND year = 2025";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) > 0) {
                throw new Exception("Section already exists!");
            }

            // check instructor constraints
            if ($instructor_id) {
                // check if instructor is already assigned to 2 sections
                $instructor_sections_sql = "SELECT COUNT(*) as count FROM section
                                            WHERE instructor_id = '$instructor_id'
                                            AND semester = 'Spring' AND year = 2025";
                $instructor_sections_result = mysqli_query($conn, $instructor_sections_sql);
                $instructor_sections_count = mysqli_fetch_assoc($instructor_sections_result)['count'];

                if ($instructor_sections_count >= 2) {
                    throw new Exception("Instructor is already assigned to 2 sections for this semester.");
                }

                // check for time slot conflicts with instructor's other sections
                if ($time_slot_id) {
                    $conflict_sql = "SELECT s.* FROM section s
                                        JOIN time_slot ts1 ON s.time_slot_id = ts1.time_slot_id
                                        JOIN time_slot ts2 ON ts2.time_slot_id = '$time_slot_id'
                                        WHERE s.instructor_id = '$instructor_id'
                                        AND s.semester = 'Spring' AND s.year = 2025
                                        AND ts1.day = ts2.day
                                        AND ((ts1.start_time <= ts2.start_time AND ts1.end_time > ts2.start_time)
                                        OR (ts1.start_time < ts2.end_time AND ts1.end_time >= ts2.end_time)
                                        OR (ts1.start_time >= ts2.start_time AND ts1.end_time <= ts2.end_time))";

                    $conflict_result = mysqli_query($conn, $conflict_sql);

                    if (mysqli_num_rows($conflict_result) > 0) {
                        throw new Exception("Time slot conflicts with instructor's existing schedule.");
                    }
                }
            }

            // check classroom constraints
            if ($classroom_id && $time_slot_id) {
                // check if classroom is already booked for this time slot
                $classroom_conflict_sql = "SELECT s.* FROM section s
                                            JOIN time_slot ts1 ON s.time_slot_id = ts1.time_slot_id
                                            JOIN time_slot ts2 ON ts2.time_slot_id = '$time_slot_id'
                                            WHERE s.classroom_id = '$classroom_id'
                                            AND s.semester = 'Spring' AND s.year = 2025
                                            AND ts1.day = ts2.day
                                            AND ((ts1.start_time <= ts2.start_time AND ts1.end_time > ts2.start_time)
                                                OR (ts1.start_time < ts2.end_time AND ts1.end_time >= ts2.end_time)
                                                OR (ts1.start_time >= ts2.start_time AND ts1.end_time <= ts2.end_time))";
                $classroom_conflict_result = mysqli_query($conn, $classroom_conflict_sql);

                if (mysqli_num_rows($classroom_conflict_result) > 0) {
                    throw new Exception("Classroom already booked for this time slot.");
                }
            }

            // insert new section
            $sql = "INSERT INTO section (course_id, section_id, semester, year, instructor_id, classroom_id, time_slot_id)
                    VALUES ('$course_id', '$section_id', 'Spring', 2025, " .
                    ($instructor_id ? "'$instructor_id'" : "NULL") . ", " .
                    ($classroom_id ? "'$classroom_id'" : "NULL") . ", " .
                    ($time_slot_id ? "'$time_slot_id'" : "NULL") . ")";

            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error creating section: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            $success_message = "Section added successfully!";

            // refresh page to show updated sections
            header("Location: admin.php");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    } else if ($_POST['action'] == 'update_section') {
        $course_id = $_POST['course_id'];
        $section_id = $_POST['section_id'];
        $instructor_id = isset($_POST['instructor_id']) && !empty($_POST['instructor_id']) ? $_POST['instructor_id'] : null;
        $classroom_id = isset($_POST['classroom_id']) && !empty($_POST['classroom_id']) ? $_POST['classroom_id'] : null;
        $time_slot_id = isset($_POST['time_slot_id']) && !empty($_POST['time_slot_id']) ? $_POST['time_slot_id'] : null;

        // start transaction
        mysqli_begin_transaction($conn);

        try {
            // check if section already exists
            $check_sql = "SELECT * FROM section WHERE course_id = '$course_id' AND section_id = '$section_id'
                            AND semester = 'Spring' AND year = 2025";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) == 0) {
                throw new Exception("Section does not exist!");
            }

            $current_section = mysqli_fetch_assoc($check_result);

            // check instructor constraints if instructor is changing
            if ($instructor_id && $instructor_id != $current_section['instructor_id']) {
                // check if instructor is already assigned to 2 sections (excluding current section)
                $instructor_sections_sql = "SELECT COUNT(*) as count FROM section
                                            WHERE instructor_id = '$instructor_id'
                                            AND semester = 'Spring' AND year = 2025
                                            AND NOT (course_id = '$course_id' AND section_id = '$section_id')";
                 $instructor_sections_result = mysqli_query($conn, $instructor_sections_sql);
                 $instructor_sections_count = mysqli_fetch_assoc($instructor_sections_result)['count'];

                 if ($instructor_sections_count >= 2) {
                    throw new Exception("Instructor is already assigned to 2 sections for this semester.");
                 }
            }

            // check for tie slot conflicts with instructor's other sections
            if ($instructor_id && $time_slot_id) {
                $conflict_sql = "SELECT s.* FROM section s
                                            JOIN time_slot ts1 ON s.time_slot_id = ts1.time_slot_id
                                            JOIN time_slot ts2 ON ts2.time_slot_id = '$time_slot_id'
                                            WHERE s.instructor_id = '$instructor_id'
                                            AND s.semester = 'Spring' AND s.year = 2025
                                            AND NOT (s.course_id = '$course_id' AND s.section_id = '$section_id')
                                            AND ts1.day = ts2.day
                                            AND ((ts1.start_time <= ts2.start_time AND ts1.end_time > ts2.start_time)
                                                OR (ts1.start_time < ts2.end_time AND ts1.end_time >= ts2.end_time)
                                                OR (ts1.start_time >= ts2.start_time AND ts1.end_time <= ts2.end_time))";
                $conflict_result = mysqli_query($conn, $conflict_sql);

                if (mysqli_num_rows($conflict_result) > 0) {
                    throw new Exception("Time slot conflict with instructor's existing schedule.");
                }
            }

            // check classroom constraints
            if ($classroom_id && $time_slot_id) {
                // check if classroom is already booked for this time slot (excluding current section)
                $classroom_conflict_sql = "SELECT s.* FROM section s
                                            JOIN time_slot ts1 ON s.time_slot_id = ts1.time_slot_id
                                            JOIN time_slot ts2 ON ts2.time_slot_id = '$time_slot_id'
                                            WHERE s.classroom_id = '$classroom_id'
                                            AND s.semester = 'Spring' AND s.year = 2025
                                            AND NOT (s.course_id = '$course_id' AND s.section_id = '$section_id')
                                            AND ts1.day = ts2.day
                                            AND ((ts1.start_time <= ts2.start_time AND ts1.end_time > ts2.start_time)
                                                OR (ts1.start_time < ts2.end_time AND ts1.end_time >= ts2.end_time)
                                                OR (ts1.start_time >= ts2.start_time AND ts1.end_time <= ts2.end_time))";
                $classroom_conflict_result = mysqli_query($conn, $classroom_conflict_sql);

                if (mysqli_num_rows($classroom_conflict_result) > 0) {
                    throw new Exception("Classroom already booked for this time slot.");
                }
            }

            // update section
            $sql = "UPDATE section SET
                    instructor_id = " . ($instructor_id ? "'$instructor_id'" : "NULL") . ",
                    classroom_id = " . ($classroom_id ? "'$classroom_id'" : "NULL") . ",
                    time_slot_id = " . ($time_slot_id ? "'$time_slot_id'" : "NULL") . "
                    WHERE course_id = '$course_id' AND section_id = '$section_id'
                    AND semester = 'Spring' AND year = 2025";

            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error updating section: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            $success_message = "Section updated successfully";

            // refresh page to show updated sections
            header("Location: admin.php");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    } else if ($_POST['action'] == 'assign_ta') {
        $course_id = $_POST['course_id'];
        $section_id = $_POST['section_id'];

        // start transaction
        mysqli_begin_transaction($conn);

        try {
            // check if section exists
            $check_sql = "SELECT * FROM section WHERE course_id = '$course_id' AND section_id = '$section_id'
                            AND semester = 'Spring' AND year = 2025";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) == 0) {
                throw new Exception("Section does not exist!");
            }

            // check if section has more than 10 students
            $student_count_sql = "SELECT COUNT(*) as count FROM take
                                    WHERE course_id = '$course_id' AND section_id = '$section_id'
                                    AND semester = 'Spring' AND year = 2025";
             $student_count_result = mysqli_query($conn, $student_count_sql);
             $student_count = mysqli_fetch_assoc($student_count_result)['count'];

             if ($student_count <= 10) {
                throw new Exception("Section does not have more than 10 students. TA not required.");
             }

             // check if TA is already assigned
             $ta_check_sql = "SELECT * FROM TA
                                WHERE course_id = '$course_id' AND section_id = '$section_id'
                                AND semester = 'Spring' AND year = 2025";
            $ta_check_result = mysqli_query($conn, $ta_check_sql);

            if (mysqli_num_rows($ta_check_result) > 0) {
                throw new Exception("TA is already assigned to this section.");
            }

            // find eligible PhD student who is not already a TA
            $phd_sql = "SELECT p.student_id, s.name FROM PhD p
                        JOIN student s ON p.student_id = s.student_id
                        WHERE NOT EXISTS (
                            SELECT 1 FROM TA
                            WHERE TA.student_id = p.student_id
                            AND TA.semester = 'Spring' AND TA.year = 2025
                        )
                        LIMIT 1";
            $phd_result = mysqli_query($conn, $phd_sql);

            if (mysqli_num_rows($phd_result) == 0) {
                throw new Exception("No eligible PhD students available for TA assignment.");
            }

            $phd_student = mysqli_fetch_assoc($phd_result);
            $ta_id = $phd_student['student_id'];

            // assign PhD student as a TA
            $assign_ta_sql = "INSERT INTO TA (student_id, course_id, section_id, semester, year)
                                VALUES ('$ta_id', '$course_id', '$section_id', 'Spring', 2025)";
            
            if (!mysqli_query($conn, $assign_ta_sql)) {
                throw new Exception("Error assigning TA: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            $success_message = "TA ({$phd_student['name']}) assigned to section successfully!";

            // refresh page to show updated sections
            header("Location: admin.php");
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
    <title>Admin Panel</title>
</head>
<body>
    <h1>Admin Panel</h1>

    <?php if (!$is_admin): ?>
        <div><strong>You do not have permission to access this page.</strong></div>
        <p><a href="logout.php">Please log out and try again</a></p>
    <?php else: ?>

        <?php if ($success_message): ?>
            <div><strong><?php echo $success_message; ?></strong></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div><strong><?php echo $error_message; ?></strong></div>
        <?php endif; ?>

        <h2>Manage Spring 2025 Sections</h2>

        <h3>Add New Section</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="add_section">

            <div>
                <label for="course_id">Course:</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>">
                            <?php echo $course['course_id'] . ': ' . $course['course_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="section_id">Section ID:</label>
                <input type="text" id="section_id" name="section_id" placeholder="e.g., Section201" required>
            </div>

            <div>
                <label for="instructor_id">Instructor:</label>
                <select id="instructor_id" name="instructor_id">
                    <option value="">-- Not Assigned --</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?php echo $instructor['instructor_id']; ?>">
                            <?php echo $instructor['instructor_name'] . ' (' . $instructor['title'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="classroom_id">Classroom:</label>
                <select id="classroom_id" name="classroom_id">
                    <option value="">-- Not Assigned --</option>
                    <?php foreach ($classrooms as $classroom): ?>
                        <option value="<?php echo $classroom['classroom_id']; ?>">
                            <?php echo $classroom['building'] . ' ' . $classroom['room_number'] . ' (Capacity: ' . $classroom['capacity'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="time_slot_id">Time Slot:</label>
                <select id="time_slot_id" name="time_slot_id">
                    <option value="">-- Not Scheduled --</option>
                    <?php foreach ($time_slots as $time_slot): ?>
                        <option value="<?php echo $time_slot['time_slot_id']; ?>">
                            <?php echo $time_slot['day'] . ' ' . date("g:i A", strtotime($time_slot['start_time'])) . ' - ' . date("g:i A", strtotime($time_slot['end_time'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Add Section</button>
        </form>

        <hr>

        <h3>Current Sections</h3>
        <table border="1">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Section</th>
                    <th>Instructor</th>
                    <th>Schedule</th>
                    <th>Location</th>
                    <th>Enrollment</th>
                    <th>TA Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $section): ?>
                    <?php
                        // check if TA is assigned
                        $ta_assigned = false;
                        $ta_info = null;

                        $ta_sql = "SELECT t.*, s.name FROM TA t
                                    JOIN student s ON t.student_id = s.student_id
                                    WHERE t.course_id = '{$section['course_id']}'
                                    AND t.section_id = '{$section['section_id']}'
                                    AND t.semester = 'Spring' AND t.year = 2025";
                        $ta_result = mysqli_query($conn, $ta_sql);

                        if (mysqli_num_rows($ta_result) > 0) {
                            $ta_assigned = true;
                            $ta_info = mysqli_fetch_assoc($ta_result);
                        }

                        // determine TA status text
                        $ta_status_text = '';

                        if ($ta_assigned) {
                            $ta_status_text = 'TA Assigned: ' . $ta_info['name'];
                        } else if ($section['enrolled_students'] > 10) {
                            $ta_status_text = 'TA Needed';
                        } else {
                            $ta_status_text = 'No TA Required';
                        }
                    ?>
                    <tr>
                        <td><?php echo $section['course_id'] . ': ' . $section['course_name']; ?></td>
                        <td><?php echo $section['section_id']; ?></td>
                        <td><?php echo $section['instructor_name'] ?? 'Not Assigned'; ?></td>
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
                        <td><?php echo $section['enrolled_students']; ?>/15</td>
                        <td><?php echo $ta_status_text; ?></td>
                        <td>
                            <!-- Edit Section Form -->
                             <form method="post" action="">
                                <input type="hidden" name="action" value="update_section">
                                <input type="hidden" name="course_id" value="<?php echo $section['course_id']; ?>">
                                <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">

                                <select name="instructor_id">
                                    <option value="">-- Select Instructor --</option>
                                    <?php foreach ($instructors as $instructor): ?>
                                        <option value="<?php echo $instructor['instructor_id']; ?>" <?php echo ($instructor['instructor_id'] == $section['instructor_id']) ? 'selected' : ''; ?>>
                                            <?php echo $instructor['instructor_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="classroom_id">
                                    <option value="">-- Select Classroom --</option>
                                    <?php foreach ($classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['classroom_id']; ?>" <?php echo ($classroom['classroom_id'] == $section['classroom_id']) ? 'selected' : ''; ?>>
                                            <?php echo $classroom['building'] . ' ' . $classroom['room_number']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="time_slot_id">
                                    <option value="">-- Select Time Slot --</option>
                                    <?php foreach ($time_slots as $time_slot): ?>
                                        <option value="<?php echo $time_slot['time_slot_id']; ?>" <?php echo ($time_slot['time_slot_id'] == $section['time_slot_id']) ? 'selected' : ''; ?>>
                                            <?php echo $time_slot['day'] . ' ' . date("g:i A", strtotime($time_slot['start_time'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit">Update</button>
                             </form>

                             <?php if ($section['enrolled_students'] > 10 && !$ta_assigned): ?>
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="assign_ta">
                                    <input type="hidden" name="course_id" value="<?php echo $section['course_id']; ?>">
                                    <input type="hidden" name="section_id" value="<?php echo $section['section_id']; ?>">
                                    <button type="submit">Assign TA</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($sections)): ?>
                    <tr>
                        <td colspan="8">No sections found for Spring 2025.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
