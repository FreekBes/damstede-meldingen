<?PHP
	session_start();

	function error_found(){
		$_SESSION["error_last_page"] = $_SERVER['REQUEST_URI'];
		header("Location: error.php", 301);
		exit();
	}
	set_error_handler('error_found', E_ALL);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<title>Meldingen Damstede Lyceum</title>
	<link rel="stylesheet" href="basicstyles.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="icon" type="image/ico" href="favicon.ico" /> 
</head>
<body>
	<form id="form" action="selector.php" method="post">
		<h1>Meldingen Damstede Lyceum</h1>
		<h3>Selecteer een actie:</h3>
		<select id="action" name="action" required>
			<option value="weergave">Weergeef meldingen</option>
			<option value="omgeving">Beheer meldingen</option>
		</select>
		<script>
		/*
		document.getElementById("action").onchange = function(event) {
			document.getElementById("form").submit();
		};
		*/
		</script>
		<input type="submit" value="Verder" />
	</form>
	<?PHP
		date_default_timezone_set('Europe/Amsterdam');
		
		$dir = getcwd();
		$files = array_merge(glob($dir."/*.php"), glob($dir."/*.html"), glob($dir."/*.js"), glob($dir."/*.css"));
		$files = array_combine($files, array_map("filemtime", $files));
		arsort($files);
		
		$latest_file = key($files);
		$last_mod = filemtime($latest_file);
	?>
	<br><br><small style="color: #999999;">Ontwikkeld door <a href="https://freekb.es/" target="_blank">Freek Bes</a>. Laatste update: <?PHP echo date("j-n-Y, H:i:s", intval($last_mod)); ?></small>
</body>
</html>