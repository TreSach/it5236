<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare an empty array of error messages
$errors = array();

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
	<?php include 'include/header.php'; ?>
	<div class="bodywrap">
	<h2>Photofolio</h2>
	<p>
		Photofolio is an picture library for customers to view and comment on their favorites. They can post pictures of something that is unique for sharing their moments.
		To get started with Photofolio, please create an account <a href="register.php">here</a> or sign in <a href="login.php">here</a> to view beautiful pictures daily.

	</p>

</div>
<div class="bodywrap">
	<p>
	NOTE: The database is removed from this website project. Microservices and protected links will be no longer functional and available. Please contact me for further microservice implementation details. I'm sorry for any inconvenience.<br><br>

	~Sach

	</p>

</div>
	<?php include 'include/footer.php'; ?>
	<script src="js/site.js"></script>
</body>
</html>
