<?php
// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare an empty array of error messages
$errors = array();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Pull the username and password from the <form> POST
	$otp = $_POST['otp'];

	// Attempt to login the user and capture the result flag
	$result = $app->otpValidate($otp, $errors);

	// Check to see if the login attempt succeeded
	if ($result == TRUE) {
		// Redirect the user to the topics page on success
		header("Location: list.php");
		exit();

	}

}





?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Photofolio</title>
	<meta name="description" content="Photofolio Spectacular">
	<meta name="author" content="Sachel Purvis">
	<link rel="stylesheet" href="css/style.css">
	<link rel="icon" href="include/img/favicon.png" type="image/x-icon" />
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css?family=Cantora+One|Russo+One" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
	body {
		background-image: url("css/images/otplogin.PNG");
		
	}
	</style>
</head>

<!--1. Display Errors if any exists
	2. Display Login form (sticky):  Username and Password -->

<body>

<div class="bodywrap">
	<h2>OTP Login Authentication</h2>

	<?php include('include/messages.php'); ?>

	<div>
		<form method="post" action="login-otp.php">

			<input type="text" name="otp" id="otp">

			<input type="submit" value="Submit OTP" name="OTP" />
		</form>
	</div>
</div>
<script src="js/site.js"></script>
</body></html>
