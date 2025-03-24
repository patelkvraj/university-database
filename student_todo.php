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
$student_id = '';
$student_info = null;
$course_todos = [];
$personal_todos = [];

// check if student_id was passed in URL or POST
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
} else if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    // store in session for future use
    $_SESSION['student_id'] = $student_id;
}

// handle form submission for adding personal todo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_todo') {
        $todo_title = mysqli_real_escape_string($conn, $_POST['todo_title']);
        $todo_description = mysqli_real_escape_string($conn, $_POST['todo_description']);
        $due_date = $_POST['due_date'];

        // insert new personal todo
        $sql = "INSERT INTO student_todo (student_id, todo_title, todo_description, due_date, is_completed)
                VALUES ('$student_id', '$todo_title', '$todo_description', '$due_date', 0)";

        if (mysqli_query($conn, $sql)) {
            $success_message = "New to-do item added successfully!";
        } else {
            $error_message = "Error adding to-do item: " . mysqli_error($conn);
        }
    }
    else if ($_POST['action'] == 'mark_completed') {
        $todo_id = $_POST['todo_id'];
        $new_status = isset($_POST['completed']) ? 1 : 0;

        $sql = "UPDATE student_todo SET is_completed = $new_status WHERE todo_id = $todo_id AND student_id = '$student_id'";

        if (mysqli_query($conn, $sql)) {
            $success_message = "To-do status updated!";
        } else {
            $error_message = "Error updating to-do status: " . mysqli_error($conn);
        }
    }
    else if ($_POST['action'] == 'delete_todo') {
        $todo_id = $_POST['todo_id'];

        $sql = "DELETE FROM student_todo WHERE todo_id = $todo_id AND student_id = '$student_id'";

        if (mysqli_query($conn, $sql)) {
            $success_message = "To-do item deleted!";
        } else {
            $error_message = "Error deleting to-do item: " . mysqli_error($conn);
        }
    }
    else if ($_POST['action'] == 'add_course_todo') {
        $event_id = $_POST['event_id'];

        // check if event_id is already in student_todo for this student
        $check_sql = "SELECT * FROM student_todo WHERE student_id = '$student_id' AND event_id = $event_id";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "This course event is already in your to-do list!";
        } else {
            // get event details
            $event_sql = "SELECT * FROM course_event WHERE event_id = $event_id";
            $event_result = mysqli_query($conn, $event_sql);

            if (mysqli_num_rows($event_result) > 0) {
                $event = mysqli_fetch_assoc($event_result);

                // add to student_todo
                $sql = "INSERT INTO student_todo (student_id, event_id, todo_title, todo_description, due_date, is_completed)
                        VALUES ('$student_id', $event_id, '{$event['event_title']}', '{$event['event_description']}', '{$event['event_date']}', 0)";

                if (mysqli_query($conn, $sql)) {
                    $success_message = "Course event added to your to-do list!";
                } else {
                    $error_message = "Error adding course event: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Course event not found!";
            }
        }
    }

    // redirect to avoid form resubmission
    header("Location: student_todo.php?student_id=$student_id");
    exit();
}

// if student_id is provided, fetch student info
if (!empty($student_id)) {
    // fetch student info
    $student_sql = "SELECT s.*,
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

    if (mysqli_num_rows($student_result) > 0) {
        $student_info = mysqli_fetch_assoc($student_result);

        // fetch student's current courses
        $courses_sql = "SELECT t.course_id, t.section_id, t.semester, t.year, c.course_name
                        FROM take t
                        JOIN course c ON t.course_id = c.course_id
                        WHERE t.student_id = '$student_id'
                        AND t.semester = 'Spring' AND t.year = 2025";

        $courses_result = mysqli_query($conn, $courses_sql);
        $current_courses = [];

        if (mysqli_num_rows($courses_result) > 0) {
            while ($row = mysqli_fetch_assoc($courses_result)) {
                $current_courses[] = $row;

                // fetch course events for this course
                $events_sql = "SELECT ce.*,
                                CASE
                                    WHEN st.todo_id IS NOT NULL THEN 1
                                    ELSE 0
                                END as in_todo_list,
                                st.todo_id, st.is_completed
                                FROM course_event ce
                                LEFT JOIN student_todo st ON ce.event_id = st.event_id AND st.student_id = '$student_id'
                                WHERE ce.course_id = '{$row['course_id']}'
                                AND ce.section_id = '{$row['section_id']}'
                                AND ce.semester = '{$row['semester']}'
                                AND ce.year = {$row['year']}
                                ORDER BY ce.event_date";

                $events_result = mysqli_query($conn, $events_sql);

                if (mysqli_num_rows($events_result) > 0) {
                    while ($event = mysqli_fetch_assoc($events_result)) {
                        $event['course_name'] = $row['course_name'];
                        $course_todos[] = $event;
                    }
                }
            }
        }

        // fetch personal todos (where event_id is NULL)
        $personal_sql = "SELECT * FROM student_todo
                        WHERE student_id = '$student_id'
                        AND event_id IS NULL
                        ORDER BY due_date, todo_title";

        $personal_result = mysqli_query($conn, $personal_sql);

        if (mysqli_num_rows($personal_result) > 0) {
            while ($row = mysqli_fetch_assoc($personal_result)) {
                $personal_todos[] = $row;
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
    <title>Student To-Do List</title>
</head>
<body>
    <h1>Student To-Do-List</h1>

    <?php if ($success_message): ?>
        <div><strong><?php echo $success_message; ?></strong></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div><strong><?php echo $error_message; ?></strong></div>
    <?php endif; ?>

    <?php if (!$student_info): ?>
        <div>
            <strong>Unable to find your student information.</strong>
            <p><a href="logout.php">Please log out and try again</a></p>
        </div>
    <?php else: ?>
        <div>
            <h2>Welcome, <?php echo $student_info['name']; ?>!</h2>
            <p><strong>Student ID:</strong> <?php echo $student_info['student_id'];?></p>
        </div>

        <h2>Add New Personal To-Do</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="add_todo">
            <div>
                <label for="todo_title">Title:</label>
                <input type="text" id="todo_title" name="todo_title" required>
            </div>
            <div>
                <label for="todo_description">Description:</label>
                <textarea id="todo_description" name="todo_description" rows="3"></textarea>
            </div>
            <div>
                <label for="due_date">Due Date:</label>
                <input type="date" id="due_date" name="due_date" required>
            </div>
            <button type="submit">Add To-Do</button>
        </form>

        <h2>Your To-Do List</h2>
        <?php if (empty($personal_todos) && empty($course_todos)): ?>
            <p>You don't have any to-do items yet.</p>
        <?php else: ?>
            <h3>Course Deadlines</h3>
            <?php if (empty($course_todos)): ?>
                <p>No course deadlines found.</p>
            <?php else: ?>
                <table border="1" width="100%">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Due Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($course_todos as $todo): ?>
                            <tr>
                                <td><?php echo $todo['course_id'] . ": " . $todo['course_name']; ?></td>
                                <td><?php echo $todo['event_title']; ?></td>
                                <td><?php echo $todo['event_description']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($todo['event_date'])); ?></td>
                                <td><?php echo ucfirst($todo['event_type']); ?></td>
                                <td>
                                    <?php if ($todo['in_todo_list']): ?>
                                        <form method="post" action="">
                                            <input type="hidden" name="action" value="mark_completed">
                                            <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                            <input type="checkbox" name="completed" onChange="this.form.submit()"
                                                <?php echo $todo['is_completed'] ? 'checked' : ''; ?>>
                                            <?php echo $todo['is_completed'] ? 'Completed' : 'Pending'; ?>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="">
                                            <input type="hidden" name="action" value="add_course_todo">
                                            <input type="hidden" name="event_id" value="<?php echo $todo['event_id']; ?>">
                                            <button type="submit">Add to To-Do List</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($todo['in_todo_list']): ?>
                                        <form method="post" action="" onsubmit="return confirm('Are you sure you want to remove this item?');">
                                            <input type="hidden" name="action" value="delete_todo">
                                            <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                            <button type="submit">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h3>Personal To-Do Items</h3>
            <?php if (empty($personal_todos)): ?>
                <p>No personal to-do items found.</p>
            <?php else: ?>
                <table border="1" width="100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personal_todos as $todo): ?>
                            <tr>
                                <td><?php echo $todo['todo_title']; ?></td>
                                <td><?php echo $todo['todo_description']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($todo['due_date'])); ?></td>
                                <td>
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="mark_completed">
                                        <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                        <input type="checkbox" name="completed" onChange="this.form.submit()"
                                            <?php echo $todo['is_completed'] ? 'checked' : ''; ?>>
                                        <?php echo $todo['is_completed'] ? 'Completed' : 'Pending'; ?>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                        <input type="hidden" name="action" value="delete_todo">
                                        <input type="hidden" name="todo_id" value="<?php echo $todo['todo_id']; ?>">
                                        <button type="submit">Delete</button>
                                    </form>
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