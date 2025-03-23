<?php

// start session
session_start();

// clear session variables
$_SESSION = array();

// delete session cookies if killing session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// destroy session
session_destroy();

// redirect to login page
header("Location: index.html");
exit();
?>