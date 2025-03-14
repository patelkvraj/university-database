<?php
include 'config.php';

// check if user is logged in and is admin
session_start();
$is_admin = false;

// for now, no authentication for admin
$is_admin = true;

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
$section_sql = "SELECT s.*, c.course_name, i.instructor_name, cl.building, cl.room_number, ts.day, ts.start_time, ts.end_time, (SELECT COUNT(*) FROM take WHERE t.course_id = s.course_id AND t.section_id = s.section_id
                    AND t.semester = s.semester AND t.year = s.year) as enrolled_students
                FROM section s
                JOIN course c ON s.course_id = c.course_id
                LEFT JOIN instructor i ON s.instructor_id = i.instructor.id
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
                                            WHERE instuctor_id = '$instructor_id'
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
                    ($time_slot_id ? "'time_slot_id'" : "NULL") . ")";

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
        
    }
 }
