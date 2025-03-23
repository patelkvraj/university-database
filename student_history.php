<?php
include 'config.php';

// init variables
$success_message = '';
$error_message = '';
$courses = [];
$student_id = '';
$student_info = null;
$total_credits_earned = 0;
$gpa = 0;

// check if student_id was passed in URL
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    // auto submit form if student_id is provided in URL
    $_SERVER["REQUEST_METHOD"] = "POST";
}

// check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : $student_id;

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

            // fetch course history
            $courses_sql = "SELECT t.course_id, t.section_id, t.semester, t.year, t.grade,
                            c.course_name, c.credits, i.instructor_name
                            FROM take t
                            JOIN course c ON t.course_id = c.course_id
                            JOIN section s ON t.course_id = s.course_id AND t.section_id = s.section_id
                                    AND t.semester = s.semester AND t.year = s.year
                            LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                            WHERE t.student_id = '$student_id'
                            ORDER BY t.year DESC, FIELD(t.semester, 'Spring', 'Summer', 'Fall', 'Winter'), t.course_id";

            $courses_result = mysqli_query($conn, $courses_sql);

            if (mysqli_num_rows($courses_result) > 0) {
                $total_grade_points = 0;
                $total_credits_attempted = 0;

                while ($row = mysqli_fetch_assoc($courses_result)) {
                    $courses[] = $row;

                    // calculate GPA if grade is available
                    if ($row['grade']) {
                        $credits = $row['credits'];
                        $grade_point = 0;

                        // convert letter grade to grade points
                        switch ($row['grade']) {
                            case 'A+': $grade_point = 4.0; break;
                            case 'A': $grade_point = 4.0; break;
                            case 'A-': $grade_point = 3.7; break;
                            case 'B+': $grade_point = 3.3; break;
                            case 'B': $grade_point = 3.0; break;
                            case 'B-': $grade_point = 2.7; break;
                            case 'C+': $grade_point = 2.3; break;
                            case 'C': $grade_point = 2.0; break;
                            case 'C-': $grade_point = 1.7; break;
                            case 'D+': $grade_point = 1.3; break;
                            case 'D': $grade_point = 1.0; break;
                            case 'D-': $grade_point = 0.7; break;
                            case 'F': $grade_point = 0; break;
                            default: $grade_point = 0.0;
                        }

                        $total_grade_points += ($grade_point * $credits);
                        $total_credits_attempted += $credits;

                        // add to total credits earned if passing grade
                        if ($grade_point >= 1.0) {
                            $total_credits_earned += $credits;
                        }
                    }
                }

                // calculate GPA
                if ($total_credits_attempted > 0) {
                    $gpa = $total_grade_points / $total_credits_attempted;
                }
            }
        } else {
            $error_message = "Student not found.";
        }
    } else {
        $error_message = "Please enter a student ID.";
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
    <title>Student Course History</title>
</head>
<body>
    <h1>Student Course History</h1>

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
        <button type="submit">Search</button>
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
                <p><strong>Class Standing:</strong> <?php echo $student_info['class_standing']; ?></p>
            <?php endif; ?>
        </div>

        <div>
            <h3>Academic Summary</h3>
            <p><strong>GPA:</strong> <?php echo number_format($gpa, 2); ?></p>
            <p><strong>Total Credits Earned:</strong> <?php echo $total_credits_earned; ?></p>
            <?php if ($student_info['student_type'] == 'undergraduate'): ?>
                <p><strong>Credits In System:</strong> <?php echo $student_info['undergrad_credits']; ?></p>
                <?php if ($total_credits_earned != $student_info['undergrad_credits']): ?>
                    <p><em>Note: Your earned credits differ from the credits in your student record. Please contact your advisor.</em></p>
                <?php endif; ?>
            <?php elseif ($student_info['student_type'] == 'master'): ?>
                <p><strong>Credits in System:</strong> <?php echo $student_info['master_credits']; ?></p>
                <?php if ($total_credits_earned != $student_info['master_credits']): ?>
                    <p><em>Note: Your earned credits differ from the credits in your student record. Please contact your advisor.</em></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <h2>Current Semester Courses (Spring 2025)</h2>
        <?php
        $current_courses = array_filter($courses, function($course) {
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
        $past_courses = array_filter($courses, function($course) {
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
                                    if ($course['grade']) {
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