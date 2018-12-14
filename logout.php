<?PHP
	session_start();
	session_destroy();
	session_start();
	if (isset($_GET["by"]) && $_GET["by"] == "auto") {
		$_SESSION["login_error"] = "Je bent automatisch uitgelogd na 10 minuten inactiviteit.";
		header("Location: login.php");
		die();
	}
	else {
		$_SESSION["login_error"] = "Je bent nu uitgelogd.";
		header("Location: login.php");
		die();
	}
?>