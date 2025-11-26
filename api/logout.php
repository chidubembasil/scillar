<?php
    session_start();
    session_destroy(); // Clears all session data
    header("Location: auth.php"); // Redirect to login page
    exit;
