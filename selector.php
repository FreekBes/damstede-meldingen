<?PHP
	if (isset($_POST["action"]) && !empty($_POST["action"])) {
		switch($_POST["action"]) {
			case "weergave":
				header("Location: meldingen.php");
				die();
				break;
			case "omgeving":
				header("Location: portal.php");
				die();
				break;
			default:
				echo "Er is een fout opgetreden. Klik <a href='index.php'>hier</a> om terug te gaan.";
				die();
				break;
		}
	}
	else {
		header("Location: index.php");
		die();
	}
?>