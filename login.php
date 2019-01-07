<?PHP
	session_start();

	function error_found(){
		$_SESSION["error_last_page"] = $_SERVER['REQUEST_URI'];
		header("Location: error.php", 301);
		exit();
	}
	set_error_handler('error_found', E_ALL);
	
	require_once("import/nogit.php");
	
	// verkrijg de opgevraagde pagina en stop deze in de SESSION array voor later gebruik.
	if (!empty($_GET["continue"])) {
		$_SESSION["login_continue"] = urldecode($_GET["continue"]);
	}
	else {
		// geen pagina opgegeven? Ga dan standaard naar portal.php
		$_SESSION["login_continue"] = "/portal.php";
	}
	
	if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
		// als er al ingelogd is, dan gaan we direct door naar de opgevraagde pagina!
		header("Location: ".$_SESSION["login_continue"]);
		unset($_SESSION["login_continue"]);
		unset($_SESSION["login_error"]);
		die();
	}
	
	if (isset($_POST["login"])) {
		// salt voor encryptie
		if (crypt($_POST["pw"], $salt) == $hashedPassword) {
			// ingevoerde wachtwoord komt overeen met het vantevoren gedefinieerde wachtwoord (encrypted)
			$_SESSION["logged_in"] = true;
			header("Location: ".$_SESSION["login_continue"]);
			unset($_SESSION["login_continue"]);
			unset($_SESSION["login_error"]);
			die();
			// ga door naar de opgevraagde pagina
		}
		else {
			// wachtwoord is incorrect.
			$_SESSION["login_error"] = "Incorrect wachtwoord";
		}
		
		// terug naar het login form aangezien er iets mis ging.
		header('Location:'.$_SERVER['PHP_SELF']."?continue=".urlencode($_SESSION["login_continue"]));
		die();
	}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<title>Log in</title>
	<link rel="stylesheet" href="basicstyles.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="icon" type="image/ico" href="favicon.ico" /> 
</head>
<body>
	<form id="form" action="login.php?continue=<?PHP echo urlencode($_SESSION["login_continue"]); ?>" method="post">
		<h1>Meldingen Damstede Lyceum</h1>
		<h3>Inloggen vereist</h3>
		<input type="password" name="pw" id="pw" placeholder="Voer uw wachtwoord in" autofocus />
		<input type="submit" name="login" value="Inloggen" />
		<br><br>
		<?PHP if (isset($_SESSION["login_error"]) && !empty($_SESSION["login_error"])) { ?><small style="color: #FF4500;"><?PHP echo $_SESSION["login_error"]; ?></small><?PHP unset($_SESSION["login_error"]); } ?>
	</form>
</body>
</html>