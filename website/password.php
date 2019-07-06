<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

$errors = array();
$messages = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

	$passwordrequestid = $_GET['id'];

}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Grab or initialize the input values
	$password = $_POST['password'];
	$passwordrequestid = $_POST['passwordrequestid'];

	// Request a password reset email message
	$app->updatePassword($password, $passwordrequestid, $errors);

	if (sizeof($errors) == 0) {
		$message = "Password updated";
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
	<link rel="icon" href="include/img/favicon.png" type="image/x-icon" />
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css?family=Cantora+One|Russo+One" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
	<script src="js/site.js"></script>
	<?php include 'include/header.php'; ?>

	<main id="wrapper">
		<div class="bodywrap">
		<h2>Reset Password</h2>
		<?php include('include/messages.php'); ?>
		<form method="post" action="password.php">
			New password:
			<input type="password" name="password" id="password" required="required" size="40" />
			<input type="submit" value="Submit" />
			<input type="hidden" name="passwordrequestid" id="passwordrequestid" value="<?php echo $passwordrequestid; ?>" />
		</form>
	</div>
	</main>
	<?php include 'include/footer.php'; ?>
</body>
</html>
