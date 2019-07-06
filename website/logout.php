<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Log the user out of the application
$app->logout();

header("Location: login.php");
exit();
?>
