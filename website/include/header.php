<?php

	// Assume the user is not logged in and not an admin
	$isadmin = FALSE;
	$loggedin = FALSE;

	// If we have a session ID cookie, we might have a session
	if (isset($_COOKIE['sessionid'])) {

		$user = $app->getSessionUser($errors);
		$loggedinuserid = $user["userid"];

		// Check to see if the user really is logged in and really is an admin
		if ($loggedinuserid != NULL) {
			$loggedin = TRUE;
			$isadmin = $app->isAdmin($errors, $loggedinuserid);
		}

	} else {

		$loggedinuserid = NULL;

	}


?>
<div class="maincov">
	<div class="nav">
		<a href="index.php"><i class="fas fa-home"></i>Home</a>
		&nbsp;&nbsp;
		<?php if (!$loggedin) { ?>
			<a href="login.php"><i class="fas fa-sign-in-alt"></i>Login</a>
			&nbsp;&nbsp;
			<a href="register.php"><i class="fas fa-key"></i>Register</a>
			&nbsp;&nbsp;
		<?php } ?>
		<?php if ($loggedin) { ?>
			<a href="list.php"><i class="fas fa-thumbtack"></i>List</a>
			&nbsp;&nbsp;
			<a href="editprofile.php"><i class="fas fa-user"></i>Profile</a>
			&nbsp;&nbsp;
			<?php if ($isadmin) { ?>
				<a href="admin.php"><i class="fas fa-user-md"></i>Admin</a>
				&nbsp;&nbsp;
			<?php } ?>
			<a href="fileviewer.php?file=include/help.txt"><i class="fas fa-question-circle"></i>Help</a>
			&nbsp;&nbsp;
			<a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
			&nbsp;&nbsp;

		<?php } ?>
	</div>
	<h1 style="margin-left:20px; margin-top:10px;"><img alt="Logo" src="css/images/logo_header.png" class="logo">Photofolio</h1>
</div>
