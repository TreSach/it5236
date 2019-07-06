<?php

// Import the application classes
require_once('include/classes.php');

// Create an instance of the Application class
$app = new Application();
$app->setup();

// Declare an empty array of error messages
$errors = array();

// Check for logged in user since this page is protected
$app->protectPage($errors);

$name = "";

// Attempt to obtain the list of things
$things = $app->getThings($errors);

// Check for url flag indicating that there was a "no thing" error.
if (isset($_GET["error"]) && $_GET["error"] == "nothing") {
	$errors[] = "Things not found.";
}

// Check for url flag indicating that a new thing was created.
if (isset($_GET["newthing"]) && $_GET["newthing"] == "success") {
	$message = "Thing successfully created.";
}

// If someone is attempting to create a new thing, the process the request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Pull the title and thing text from the <form> POST
	$name = $_POST['name'];
	$attachment = $_FILES['attachment'];

	// Attempt to create the new thing and capture the result flag
	$result = $app->addThing($name, $attachment, $errors);

	// Check to see if the new thing attempt succeeded
	if ($result == TRUE) {

		// Redirect the user to the login page on success
	    header("Location: list.php?newthing=success");
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
	<link rel="icon" href="include/img/favicon.png" type="image/x-icon" />
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css?family=Cantora+One|Russo+One" rel="stylesheet">
	<link rel="stylesheet" href="css/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
	div form input[type="text"], div form input[type="password"], div form textarea {
	min-width: 100%;
}
</style>
</head>

<!--1. Display Errors if any exists
	2. If no errors display things -->
<body>
	<?php include 'include/header.php'; ?>

	<div class="bodywrap">
	<h2>My Things</h2>

	<?php include('include/messages.php'); ?>
		<form action="list.php" method="post">
			<label for="search">Filter:</label>
			<input type="text" size="45" id="search" name="search"/>
			<input type="submit" value="Apply" />
		</form>

<div class="cover" align="center">

	
	<div class="lists">
		<?php if (sizeof($things) == 0) { ?>

	<div>No things found</div>
		<?php } ?>
		<?php foreach ($things as $thing) { ?>
		<!--li>-->
		<div class="item">
			<a href="thing.php?thingid=<?php echo $thing['thingid']; ?>"><?php echo $thing['thingname']; ?></a><br>
			<span class="author"><?php echo $thing['thingcreated']; ?></span>
	<!--	</li>-->
</div>
		<?php } ?>
</div>
<!--	</ul>-->
	<div class="newthing">
		<form enctype="multipart/form-data" method="post" action="list.php">
			<input type="text" name="name" id="name" placeholder="Enter a thing name" value="<?php echo $name; ?>" />
			<br/>
			<label for="attachment">Add an image, PDF, etc.</label>
			<input id="attachment" name="attachment" type="file">
			<br/>
			<input type="submit" name="start" value="Create Thing" />
			<input type="submit" name="cancel" value="Cancel" />
		</form>
	</div>
</div>
</div>
	<?php include 'include/footer.php'; ?>
	<script src="js/site.js"></script>
</body>
</html>
