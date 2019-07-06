<div class="footer fcov" align="center">Copyright &copy; <?php echo date("Y"); ?> Sachel Purvis<br>Template provided by Russell Thackston</p>
<?php

if ($_COOKIE['debug'] == "true") {
	echo "<h3>Debug messages</h3>";
	echo "<pre>";
    foreach ($app->debugMessages as $msg) {
		var_dump($msg);
	}
	echo "</pre>";
}

?>
