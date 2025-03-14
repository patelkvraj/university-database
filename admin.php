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
$section_sql = 
