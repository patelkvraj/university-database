<?php
// start session
session_start();

include 'config.php';

// check if user is logged in as admin, instructor, or not
$is_admin = false;
$is_instructor = false;
$instructor_id = '';

// check permissions
if (isset($_SESSION['logged_in'])) {
    if ($_SESSION['account_type'] == 'admin') {
        $is_admin = true;
    } else if ($_SESSION['account_type'] == 'instructor') {
        $is_instructor = true;
        $instructor_id = $_SESSION['instructor_id'];
    }
}

// init variables
$success_message = '';
$error_message = '';
$phd_students = [];
$instructors = [];
$advisor_assignments = [];
$selected_student = '';
$student_courses = [];

// fetch all PhD students
$phd_sql = "SELECT p.student_id, s.name, s.email, s.dept_name, 
                  p.qualifier, p.proposal_defence_date, p.dissertation_defence_date 
           FROM PhD p
           JOIN student s ON p.student_id = s.student_id
           ORDER BY s.name";
$phd_result = mysqli_query($conn, $phd_sql);

if ($phd_result) {
    while($row = mysqli_fetch_assoc($phd_result)) {
        $phd_students[] = $row;
    }
}

// fetch all instructors for admin
if ($is_admin) {
    $instructors_sql = "SELECT instructor_id, instructor_name, title, dept_name, email 
                      FROM instructor 
                      ORDER BY instructor_name";
    $instructors_result = mysqli_query($conn, $instructors_sql);

    if ($instructors_result) {
        while($row = mysqli_fetch_assoc($instructors_result)) {
            $instructors[] = $row;
        }
    }
}

// fetch existing advisor assignments
if ($is_admin) {
    $advise_sql = "SELECT a.instructor_id, a.student_id, a.start_date, a.end_date,
                    i.instructor_name, s.name as student_name, s.dept_name
                  FROM advise a
                  JOIN instructor i ON a.instructor_id = i.instructor_id
                  JOIN student s ON a.student_id = s.student_id
                  ORDER BY a.start_date DESC";
} else if ($is_instructor) {
    $advise_sql = "SELECT a.instructor_id, a.student_id, a.start_date, a.end_date,
                    i.instructor_name, s.name as student_name, s.dept_name
                  FROM advise a
                  JOIN instructor i ON a.instructor_id = i.instructor_id
                  JOIN student s ON a.student_id = s.student_id
                  WHERE a.instructor_id = '$instructor_id'
                  ORDER BY a.start_date DESC";
}

if (isset($advise_sql)) {
    $advise_result = mysqli_query($conn, $advise_sql);

    if ($advise_result) {
        while($row = mysqli_fetch_assoc($advise_result)) {
            $advisor_assignments[] = $row;
        }
    }
}

// handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assign advisor
    if (isset($_POST['action']) && $_POST['action'] == 'assign_advisor') {
        $student_id = $_POST['student_id'];
        $instructor_id = $_POST['instructor_id'];
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? "'" . $_POST['end_date'] . "'" : "NULL";

        // Check if this student already has this advisor
        $check_sql = "SELECT * FROM advise 
                    WHERE student_id = '$student_id' 
                    AND instructor_id = '$instructor_id'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            // Update existing record
            $update_sql = "UPDATE advise 
                          SET start_date = '$start_date', 
                              end_date = $end_date
                          WHERE student_id = '$student_id' 
                          AND instructor_id = '$instructor_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $success_message = "Advisor assignment updated successfully!";
            } else {
                $error_message = "Error updating advisor assignment: " . mysqli_error($conn);
            }
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO advise (instructor_id, student_id, start_date, end_date)
                          VALUES ('$instructor_id', '$student_id', '$start_date', $end_date)";
            
            if (mysqli_query($conn, $insert_sql)) {
                $success_message = "Advisor assigned successfully!";
            } else {
                $error_message = "Error assigning advisor: " . mysqli_error($conn);
            }
        }

        // Refresh the advisor assignments list
        if ($is_admin) {
            $advise_sql = "SELECT a.instructor_id, a.student_id, a.start_date, a.end_date,
                            i.instructor_name, s.name as student_name, s.dept_name
                          FROM advise a
                          JOIN instructor i ON a.instructor_id = i.instructor_id
                          JOIN student s ON a.student_id = s.student_id
                          ORDER BY a.start_date DESC";
        } else if ($is_instructor) {
            $advise_sql = "SELECT a.instructor_id, a.student_id, a.start_date, a.end_date,
                            i.instructor_name, s.name as student_name, s.dept_name
                          FROM advise a
                          JOIN instructor i ON a.instructor_id = i.instructor_id
                          JOIN student s ON a.student_id = s.student_id
                          WHERE a.instructor_id = '$instructor_id'
                          ORDER BY a.start_date DESC";
        }

        $advisor_assignments = [];
        $advise_result = mysqli_query($conn, $advise_sql);

        if ($advise_result) {
            while($row = mysqli_fetch_assoc($advise_result)) {
                $advisor_assignments[] = $row;
            }
        }
    }
    
    // Update PhD student information
    else if (isset($_POST['action']) && $_POST['action'] == 'update_phd') {
        $student_id = $_POST['student_id'];
        $qualifier = $_POST['qualifier'];
        $proposal_date = !empty($_POST['proposal_date']) ? "'" . $_POST['proposal_date'] . "'" : "NULL";
        $dissertation_date = !empty($_POST['dissertation_date']) ? "'" . $_POST['dissertation_date'] . "'" : "NULL";

        // Update PhD record
        $update_sql = "UPDATE PhD 
                      SET qualifier = '$qualifier',
                          proposal_defence_date = $proposal_date,
                          dissertation_defence_date = $dissertation_date
                      WHERE student_id = '$student_id'";
        
        if (mysqli_query($conn, $update_sql)) {
            $success_message = "PhD student information updated successfully!";
            
            // Refresh PhD students list
            $phd_students = [];
            $phd_sql = "SELECT p.student_id, s.name, s.email, s.dept_name, 
                          p.qualifier, p.proposal_defence_date, p.dissertation_defence_date 
                       FROM PhD p
                       JOIN student s ON p.student_id = s.student_id
                       ORDER BY s.name";
            $phd_result = mysqli_query($conn, $phd_sql);

            if ($phd_result) {
                while($row = mysqli_fetch_assoc($phd_result)) {
                    $phd_students[] = $row;
                }
            }
        } else {
            $error_message = "Error updating PhD student information: " . mysqli_error($conn);
        }
    }
}

// Handle student selection for viewing courses
if (isset($_GET['view_student'])) {
    $selected_student = $_GET['view_student'];
    
    // Fetch student's course history
    $courses_sql = "SELECT t.course_id, t.section_id, t.semester, t.year, t.grade,
                    c.course_name, c.credits, i.instructor_name
                    FROM take t
                    JOIN course c ON t.course_id = c.course_id
                    JOIN section s ON t.course_id = s.course_id AND t.section_id = s.section_id
                            AND t.semester = s.semester AND t.year = s.year
                    LEFT JOIN instructor i ON s.instructor_id = i.instructor_id
                    WHERE t.student_id = '$selected_student'
                    ORDER BY t.year DESC, FIELD(t.semester, 'Spring', 'Summer', 'Fall', 'Winter'), t.course_id";
    
    $courses_result = mysqli_query($conn, $courses_sql);
    
    if ($courses_result) {
        while($row = mysqli_fetch_assoc($courses_result)) {
            $student_courses[] = $row;
        }
    }
}

// Include header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhD Student Advisors</title>
</head>
<body>
    <h1>PhD Student Advisors</h1>

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

        <!-- Current Advisor Assignments -->
        <h2>Current Advisor Assignments</h2>
        <?php if (empty($advisor_assignments)): ?>
            <p>No advisor assignments found.</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Advisor</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($advisor_assignments as $assignment): ?>
                        <tr>
                            <td><?php echo $assignment['student_name'] . ' (' . $assignment['student_id'] . ')'; ?></td>
                            <td><?php echo $assignment['dept_name']; ?></td>
                            <td><?php echo $assignment['instructor_name'] . ' (' . $assignment['instructor_id'] . ')'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($assignment['start_date'])); ?></td>
                            <td><?php echo $assignment['end_date'] ? date('Y-m-d', strtotime($assignment['end_date'])) : 'Active'; ?></td>
                            <td>
                                <a href="phd_advisors.php?view_student=<?php echo $assignment['student_id']; ?>">
                                    View Student Courses
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Admin Only: Assign New Advisor -->
        <?php if ($is_admin): ?>
            <h2>Assign New Advisor</h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="assign_advisor">
                
                <div>
                    <label for="student_id">PhD Student:</label>
                    <select id="student_id" name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($phd_students as $student): ?>
                            <option value="<?php echo $student['student_id']; ?>">
                                <?php echo $student['name'] . ' (' . $student['student_id'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="instructor_id">Advisor:</label>
                    <select id="instructor_id" name="instructor_id" required>
                        <option value="">Select Advisor</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['instructor_id']; ?>">
                                <?php echo $instructor['instructor_name'] . ' (' . $instructor['title'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                
                <div>
                    <label for="end_date">End Date (Optional):</label>
                    <input type="date" id="end_date" name="end_date">
                </div>
                
                <button type="submit">Assign Advisor</button>
            </form>
        <?php endif; ?>

        <!-- List PhD Students -->
        <h2>PhD Students</h2>
        <?php if (empty($phd_students)): ?>
            <p>No PhD students found.</p>
        <?php else: ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Qualifier Status</th>
                        <th>Proposal Defence</th>
                        <th>Dissertation Defence</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($phd_students as $student): ?>
                        <?php
                        // Check if this instructor is an advisor for this student
                        $can_edit = $is_admin; // Admin can edit all
                        if (!$can_edit && $is_instructor) {
                            foreach ($advisor_assignments as $assignment) {
                                if ($assignment['student_id'] == $student['student_id'] && 
                                    $assignment['instructor_id'] == $instructor_id) {
                                    $can_edit = true;
                                    break;
                                }
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo $student['student_id']; ?></td>
                            <td><?php echo $student['name']; ?></td>
                            <td><?php echo $student['email']; ?></td>
                            <td><?php echo $student['dept_name']; ?></td>
                            <td><?php echo $student['qualifier'] ?: 'Not Started'; ?></td>
                            <td><?php echo $student['proposal_defence_date'] ? date('Y-m-d', strtotime($student['proposal_defence_date'])) : 'Not Scheduled'; ?></td>
                            <td><?php echo $student['dissertation_defence_date'] ? date('Y-m-d', strtotime($student['dissertation_defence_date'])) : 'Not Scheduled'; ?></td>
                            <td>
                                <?php if ($can_edit): ?>
                                    <button onclick="toggleEditForm('<?php echo $student['student_id']; ?>')">Edit</button>
                                <?php endif; ?>
                                <a href="phd_advisors.php?view_student=<?php echo $student['student_id']; ?>">
                                    View Courses
                                </a>
                            </td>
                        </tr>
                        <?php if ($can_edit): ?>
                            <tr id="edit_form_<?php echo $student['student_id']; ?>" style="display: none;">
                                <td colspan="8">
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="update_phd">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        
                                        <div>
                                            <label for="qualifier_<?php echo $student['student_id']; ?>">Qualifier Status:</label>
                                            <select id="qualifier_<?php echo $student['student_id']; ?>" name="qualifier">
                                                <option value="Not Started" <?php echo $student['qualifier'] == 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
                                                <option value="Scheduled" <?php echo $student['qualifier'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                <option value="Passed" <?php echo $student['qualifier'] == 'Passed' ? 'selected' : ''; ?>>Passed</option>
                                                <option value="Failed" <?php echo $student['qualifier'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="proposal_date_<?php echo $student['student_id']; ?>">Proposal Defence Date:</label>
                                            <input type="date" id="proposal_date_<?php echo $student['student_id']; ?>" name="proposal_date" 
                                                value="<?php echo $student['proposal_defence_date'] ? date('Y-m-d', strtotime($student['proposal_defence_date'])) : ''; ?>">
                                        </div>
                                        
                                        <div>
                                            <label for="dissertation_date_<?php echo $student['student_id']; ?>">Dissertation Defence Date:</label>
                                            <input type="date" id="dissertation_date_<?php echo $student['student_id']; ?>" name="dissertation_date" 
                                                value="<?php echo $student['dissertation_defence_date'] ? date('Y-m-d', strtotime($student['dissertation_defence_date'])) : ''; ?>">
                                        </div>
                                        
                                        <button type="submit">Update</button>
                                        <button type="button" onclick="toggleEditForm('<?php echo $student['student_id']; ?>')">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- View Selected Student Courses -->
        <?php if ($selected_student): ?>
            <?php
            // Find student name
            $student_name = '';
            foreach ($phd_students as $student) {
                if ($student['student_id'] == $selected_student) {
                    $student_name = $student['name'];
                    break;
                }
            }
            ?>
            <h2>Course History for <?php echo $student_name . ' (' . $selected_student . ')'; ?></h2>
            <?php if (empty($student_courses)): ?>
                <p>No course history found for this student.</p>
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
                        <?php foreach ($student_courses as $course): ?>
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
    <?php endif; ?>

    <script>
        function toggleEditForm(studentId) {
            var form = document.getElementById('edit_form_' + studentId);
            if (form.style.display === 'none') {
                form.style.display = 'table-row';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>