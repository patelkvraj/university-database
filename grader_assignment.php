<?php
// start session
session_start();

include 'config.php';

// check if user is logged in and is admin or instructor
$is_admin = false;
$is_instructor = false;

// check if logged in as admin
if (isset($_SESSION['logged_in']) && $_SESSION['account_type'] == 'admin') {
    $is_admin = true;
} else if (isset($_SESSION['logged_in']) && $_SESSION['account_type'] == 'instructor'){
    $is_instructor = true;
}

// if not admin or instructor, redirectto login
if (!$is_admin && !$is_instructor) {
    header("Location: index.html");
    exit();
}

// init variables
$success_message = '';
$error_message = '';
$instructor_id = '';
$sections = [];
$eligible_students = [];
$selected_section = '';

// get instructor_id from session if instructor is logged in
if ($is_instructor && isset($_SESSION['instructor_id'])) {
    $instructor_id = $_SESSION['instructor_id'];
}

// fetch sections that need graders (5-10 students)
if ($is_admin) {
    // admin sees all sections that need graders
    $sections_sql = "SELECT s.*, c.course_name, i.instructor_name, 
                      (SELECT COUNT(*) FROM take t 
                       WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                       AND t.semester = s.semester AND t.year = s.year) as enrolled_students,
                      (SELECT COUNT(*) FROM masterGrader mg 
                       WHERE mg.course_id = s.course_id AND mg.section_id = s.section_id
                       AND mg.semester = s.semester AND mg.year = s.year) as has_ms_grader,
                      (SELECT COUNT(*) FROM undergraduateGrader ug 
                       WHERE ug.course_id = s.course_id AND ug.section_id = s.section_id
                       AND ug.semester = s.semester AND ug.year = s.year) as has_ug_grader
                    FROM section s
                    JOIN course c ON s.course_id = c.course_id
                    LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                    WHERE s.semester = 'Spring' AND s.year = 2025
                    HAVING enrolled_students BETWEEN 5 AND 10
                    AND (has_ms_grader = 0 AND has_ug_grader = 0)
                    ORDER BY s.course_id, s.section_id";
} else {
    // instructor sees only their sections that need graders
    $sections_sql = "SELECT s.*, c.course_name, i.instructor_name, 
                      (SELECT COUNT(*) FROM take t 
                       WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                       AND t.semester = s.semester AND t.year = s.year) as enrolled_students,
                      (SELECT COUNT(*) FROM masterGrader mg 
                       WHERE mg.course_id = s.course_id AND mg.section_id = s.section_id
                       AND mg.semester = s.semester AND mg.year = s.year) as has_ms_grader,
                      (SELECT COUNT(*) FROM undergraduateGrader ug 
                       WHERE ug.course_id = s.course_id AND ug.section_id = s.section_id
                       AND ug.semester = s.semester AND ug.year = s.year) as has_ug_grader
                    FROM section s
                    JOIN course c ON s.course_id = c.course_id
                    LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                    WHERE s.semester = 'Spring' AND s.year = 2025
                    AND s.instructor_id = '$instructor_id'
                    HAVING enrolled_students BETWEEN 5 AND 10
                    AND (has_ms_grader = 0 AND has_ug_grader = 0)
                    ORDER BY s.course_id, s.section_id";
}

$sections_result = mysqli_query($conn, $sections_sql);

if ($sections_result) {
    while($row = mysqli_fetch_assoc($sections_result)) {
        $sections[] = $row;
    }
}

// handle section selection for finding eligible graders
if (isset($_GET['section'])) {
    $selected_section = $_GET['section'];
    list($course_id, $section_id, $semester, $year) = explode('|', $selected_section);

    // get eligible MS students (enrolled in diff sections of same course, not already grader)
    $ms_students_sql = "SELECT s.student_id, s.name, s.email, 'MS' as student_type
                        FROM student s
                        JOIN master m ON s.student_id = m.student_id
                        WHERE NOT EXISTS (
                            SELECT 1 FROM masterGrader mg
                            WHERE mg.student_id = s.student_id
                            AND mg.semester = '$semester' AND mg.year = $year
                        )
                        ORDER BY s.name";

    $ms_students_result = mysqli_query($conn, $ms_students_sql);

    if ($ms_students_result) {
        while ($row = mysqli_fetch_assoc($ms_students_result)) {
            $eligible_students[] = $row;
        }
    }

    // get eligible undergrad students (who have taken the course and got A or A-)
    $ug_students_sql = "SELECT s.student_id, s.name, s.email, t.grade, 'UG' as student_type
                        FROM student s
                        JOIN undergraduate u ON s.student_id = u.student_id
                        JOIN take t ON s.student_id = t.student_id
                        WHERE t.course_id = '$course_id'
                        AND t.grade IN ('A', 'A-')
                        AND NOT EXISTS (
                            SELECT 1 FROM undergraduateGrader ug
                            WHERE ug.student_id = s.student_id
                            AND ug.semester = '$semester' AND ug.year = $year
                        )
                        ORDER BY s.name";

    $ug_students_result = mysqli_query($conn, $ug_students_sql);

    if ($ug_students_result) {
        while($row = mysqli_fetch_assoc($ug_students_result)) {
            $eligible_students[] = $row;
        }
    }
}

// handle assigning a grader
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_grader') {
    $student_id = $_POST['student_id'];
    $student_type = $_POST['student_type'];
    list($course_id, $section_id, $semester, $year) = explode('|', $_POST['section']);

    // start transaction
    mysqli_begin_transaction($conn);

    try {
        // check if this section already has a grader
        $check_sql = "SELECT 
                        (SELECT COUNT(*) FROM masterGrader mg 
                         WHERE mg.course_id = '$course_id' AND mg.section_id = '$section_id'
                         AND mg.semester = '$semester' AND mg.year = $year) as ms_grader_count,
                        (SELECT COUNT(*) FROM undergraduateGrader ug 
                         WHERE ug.course_id = '$course_id' AND ug.section_id = '$section_id'
                         AND ug.semester = '$semester' AND ug.year = $year) as ug_grader_count";
        
        $check_result = mysqli_query($conn, $check_sql);
        $grader_counts = mysqli_fetch_assoc($check_result);
        
        if ($grader_counts['ms_grader_count'] > 0 || $grader_counts['ug_grader_count'] > 0) {
            throw new Exception("This section already has a grader assigned.");
        }

        // check if student is already a grader elsewhere
        if ($student_type == 'MS') {
            $student_check_sql = "SELECT COUNT(*) as count FROM masterGrader
                                  WHERE student_id = '$student_id'
                                  AND semester = '$semester' AND year = $year";
        } else {
            $student_check_sql = "SELECT COUNT(*) as count FROM undergraduateGrader
                                  WHERE student_id = '$student_id'
                                  AND semester = '$semester' AND year = $year";
        }
        
        $student_check_result = mysqli_query($conn, $student_check_sql);
        $student_check = mysqli_fetch_assoc($student_check_result);
        
        if ($student_check['count'] > 0) {
            throw new Exception("This student is already assigned as a grader for another section.");
        }

        // assign the grader
        if ($student_type == 'MS') {
            $assign_sql = "INSERT INTO masterGrader (student_id, course_id, section_id, semester, year)
                          VALUES ('$student_id', '$course_id', '$section_id', '$semester', $year)";
        } else {
            $assign_sql = "INSERT INTO undergraduateGrader (student_id, course_id, section_id, semester, year)
                          VALUES ('$student_id', '$course_id', '$section_id', '$semester', $year)";
        }
        
        if (!mysqli_query($conn, $assign_sql)) {
            throw new Exception("Error assigning grader: " . mysqli_error($conn));
        }

        // commit transaction
        mysqli_commit($conn);
        $success_message = "Grader assigned successfully!";

        // refresh page
        header("Location: grader_assignment.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = $e->getMessage();
    }
}

// fetch sections with assigned graders
if ($is_admin) {
    $assigned_sql = "SELECT s.course_id, s.section_id, s.semester, s.year, c.course_name, i.instructor_name,
                      (SELECT COUNT(*) FROM take t 
                       WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                       AND t.semester = s.semester AND t.year = s.year) as enrolled_students,
                      CASE
                        WHEN mg.student_id IS NOT NULL THEN CONCAT(ms.name, ' (MS)')
                        WHEN ug.student_id IS NOT NULL THEN CONCAT(us.name, ' (UG)')
                        ELSE NULL
                      END as grader_name,
                      CASE
                        WHEN mg.student_id IS NOT NULL THEN mg.student_id
                        WHEN ug.student_id IS NOT NULL THEN ug.student_id
                        ELSE NULL
                      END as grader_id
                    FROM section s
                    JOIN course c ON s.course_id = c.course_id
                    LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                    LEFT JOIN masterGrader mg ON s.course_id = mg.course_id AND s.section_id = mg.section_id
                           AND s.semester = mg.semester AND s.year = mg.year
                    LEFT JOIN undergraduateGrader ug ON s.course_id = ug.course_id AND s.section_id = ug.section_id
                           AND s.semester = ug.semester AND s.year = ug.year
                    LEFT JOIN student ms ON mg.student_id = ms.student_id
                    LEFT JOIN student us ON ug.student_id = us.student_id
                    WHERE s.semester = 'Spring' AND s.year = 2025
                    AND (mg.student_id IS NOT NULL OR ug.student_id IS NOT NULL)
                    ORDER BY s.course_id, s.section_id";
} else {
    $assigned_sql = "SELECT s.course_id, s.section_id, s.semester, s.year, c.course_name, i.instructor_name,
                      (SELECT COUNT(*) FROM take t 
                       WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                       AND t.semester = s.semester AND t.year = s.year) as enrolled_students,
                      CASE
                        WHEN mg.student_id IS NOT NULL THEN CONCAT(ms.name, ' (MS)')
                        WHEN ug.student_id IS NOT NULL THEN CONCAT(us.name, ' (UG)')
                        ELSE NULL
                      END as grader_name,
                      CASE
                        WHEN mg.student_id IS NOT NULL THEN mg.student_id
                        WHEN ug.student_id IS NOT NULL THEN ug.student_id
                        ELSE NULL
                      END as grader_id
                    FROM section s
                    JOIN course c ON s.course_id = c.course_id
                    LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                    LEFT JOIN masterGrader mg ON s.course_id = mg.course_id AND s.section_id = mg.section_id
                           AND s.semester = mg.semester AND s.year = mg.year
                    LEFT JOIN undergraduateGrader ug ON s.course_id = ug.course_id AND s.section_id = ug.section_id
                           AND s.semester = ug.semester AND s.year = ug.year
                    LEFT JOIN student ms ON mg.student_id = ms.student_id
                    LEFT JOIN student us ON ug.student_id = us.student_id
                    WHERE s.semester = 'Spring' AND s.year = 2025
                    AND s.instructor_id = '$instructor_id'
                    AND (mg.student_id IS NOT NULL OR ug.student_id IS NOT NULL)
                    ORDER BY s.course_id, s.section_id";
}

$assigned_result = mysqli_query($conn, $assigned_sql);
$assigned_sections = [];

if ($assigned_result) {
    while($row = mysqli_fetch_assoc($assigned_result)) {
        $assigned_sections[] = $row;
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
    <title>Grader Assignment</title>
</head>
<body>
    <h1>Grader Assignment</h1>

    <?php if (!$is_admin && !$is_instructor): ?>
        <div><strong>You do not have permission to access this page.</strong></div>
        <p><a href="logout.php">Please log out and try again</a></p>
    <?php else: ?>

        <?php if ($success_message): ?>
            <div><strong><?php echo $success_message; ?></strong></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div><strong><?php echo $error_message; ?></strong></div>
        <?php endif; ?>

        <h2>Sections Needing Graders (5-10 Students)</h2>
        <?php if (empty($sections)): ?>
            <p>No sections currently need graders.</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Instructor</th>
                        <th>Enrollment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><?php echo $section['course_id'] . ': ' . $section['course_name']; ?></td>
                            <td><?php echo $section['section_id']; ?></td>
                            <td><?php echo $section['instructor_name'] ?? 'Not Assigned'; ?></td>
                            <td><?php echo $section['enrolled_students']; ?>/15</td>
                            <td>
                                <a href="grader_assignment.php?section=<?php echo $section['course_id'] . '|' . $section['section_id'] . '|' . $section['semester'] . '|' . $section['year']; ?>">
                                    Find Eligible Graders
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($selected_section): ?>
            <h2>Eligible Graders for Selected Section</h2>
            <?php list($course_id, $section_id, $semester, $year) = explode('|', $selected_section); ?>
            <h3>Course: <?php echo $course_id . ' - Section: ' . $section_id; ?></h3>
            
            <?php if (empty($eligible_students)): ?>
                <p>No eligible students found to serve as graders for this section.</p>
            <?php else: ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Grade (UG only)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligible_students as $student): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['email']; ?></td>
                                <td><?php echo $student['student_type']; ?></td>
                                <td><?php echo isset($student['grade']) ? $student['grade'] : 'N/A'; ?></td>
                                <td>
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="assign_grader">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <input type="hidden" name="student_type" value="<?php echo $student['student_type']; ?>">
                                        <input type="hidden" name="section" value="<?php echo $selected_section; ?>">
                                        <button type="submit">Assign as Grader</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <h2>Sections with Assigned Graders</h2>
        <?php if (empty($assigned_sections)): ?>
            <p>No sections currently have assigned graders.</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Instructor</th>
                        <th>Enrollment</th>
                        <th>Assigned Grader</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assigned_sections as $section): ?>
                        <tr>
                            <td><?php echo $section['course_id'] . ': ' . $section['course_name']; ?></td>
                            <td><?php echo $section['section_id']; ?></td>
                            <td><?php echo $section['instructor_name'] ?? 'Not Assigned'; ?></td>
                            <td><?php echo $section['enrolled_students']; ?>/15</td>
                            <td><?php echo $section['grader_name'] . ' (' . $section['grader_id'] . ')'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>