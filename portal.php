<?PHP
	// error_reporting(E_ALL); ini_set('display_errors', 1);
	
	session_start();

	date_default_timezone_set('Europe/Amsterdam');
	
	
	if (!isset($_SESSION["view"])) {
		$_SESSION["view"] = "previews";
	}
	if (isset($_GET["view"]) && !empty($_GET["view"])) {
		$_SESSION["view"] = $_GET["view"];
	}
	
	if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
		header("Location: login.php?continue=portal.php?".http_build_query($_GET));
		die();
	}
	
	// verkrijg alle beschikbare schermen
	$screens = json_decode(file_get_contents("screens.json"), true);
	
	// verwijder een melding
	if (isset($_GET["del"]) && !empty($_GET["del"])) {
		$del = explode("-", $_GET["del"]);
		// todo is het id van het scherm
		$todo = $del[0];
		// todel is het id van de te verwijderen melding
		$todel = implode("-", $del);
		
		// verkrijg alle meldingen voor het scherm en cycle hier doorheen
		$todonots = json_decode(file_get_contents($screens[$todo]["file"]), true);
		for ($i = 0; $i < count($todonots["messages"]); $i++) {
			// controleer het id van deze melding
			if ($todonots["messages"][$i]["id"] == $todel) {
				// als de id van deze melding overeen komt met dat van die we willen verwijderen, verwijder de melding en stop de loop
				if (isset($todonots["messages"][$i]["src"]) && !empty($todonots["messages"][$i]["src"])) {
					// verwijder het bijbehorende bestand (afbeelding of video) van de server, indien deze bij een melding zat
					unlink($todonots["messages"][$i]["src"]);
				}
				unset($todonots["messages"][$i]);
				$todonots["messages"] = array_values($todonots["messages"]);
				break;
			}
		}
		$todonots = json_encode($todonots, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		file_put_contents($screens[$todo]["file"], $todonots);
		
		// refresh zonder GET gegevens
		header("Location: portal.php?view=".$_SESSION["view"]);
		die();
	}
	
	// voeg een melding toe
	if (isset($_POST["addnot"]) && count($_POST["screens"]) > 0) {
		$fileMoved = false;
		$otherLoc = null;
		$extension = null;
		
		// voor elk geselecteerde scherm voer onderstaande code uit
		foreach($_POST["screens"] as $todo) {
			// verkrijg alle meldingen voor het scherm
			$todonots = json_decode(file_get_contents($screens[$todo]["file"]), true);
			$toadd = array();
			$toadd["id"] = $todo."-".time();
			$toadd["added"] = time();
			$toadd["modified"] = time();
			$toadd["type"] = $_POST["type"];
			if (isset($_POST["start-date"]) && !empty($_POST["start-date"])) {
				$startDate = strtotime($_POST["start-date"]);
				if ($startDate !== false) {
					$toadd["start-date"] = $startDate;
				}
				else {
					$toadd["start-date"] = null;
				}
			}
			else {
				$toadd["start-date"] = null;
			}
			
			if (isset($_POST["end-date"]) && !empty($_POST["end-date"])) {
				$endDate = strtotime($_POST["end-date"]);
				if ($endDate !== false) {
					$toadd["end-date"] = $endDate;
				}
				else {
					$toadd["end-date"] = null;
				}
			}
			else {
				$toadd["end-date"] = null;
			}
			
			if ($_POST["type"] == "default") {
				if (empty($_POST["title"])) {
					// title is leeg!
					die();
				}
				$toadd["title"] = $_POST["title"];
			}
			else {
				$toadd["title"] = "";
			}
			if ($_POST["type"] == "default") {
				// content wordt alleen letterlijk overgenomen bij een default type
				if (empty($_POST["content"])) {
					// content is leeg!
					die();
				}
				// vervang line breaks met html line breaks
				$toadd["content"] = str_replace(PHP_EOL, "<br>", $_POST["content"]);
				$toadd["src"] = null;
			}
			else if ($_POST["type"] == "image") {
				if (isset($_FILES["image"]) && !$fileMoved) {
					if ($_FILES["image"]["error"] === UPLOAD_ERR_OK) {
						if ($_FILES["image"]["size"] > 0) {
							$temp = explode(".", $_FILES["image"]["name"]);
							$newLoc = "content/".$toadd["id"].".".end($temp);
							if (move_uploaded_file($_FILES["image"]["tmp_name"], $newLoc) === true) {
								$fileMoved = true;
								$otherLoc = $newLoc;
								$extension = $temp;
								$toadd["content"] = 'Afbeeldingen worden nog niet ondersteund in deze weergave.';
								$toadd["src"] = $newLoc;
							}
							else {
								// er ging iets mis bij het verplaatsen van het geuploadde bestand naar de juiste locatie
								break;
							}
						}
						else {
							// bestandsgrootte is 0, is het bestand correct geupload?
							break;
						}
					}
					else {
						// er ging iets fout bij het uploaden
						break;
					}
				}
				else if ($fileMoved) {
					$newLoc = "content/".$toadd["id"].".".end($extension);
					copy($otherLoc, $newLoc);
					$toadd["content"] = 'Afbeeldingen worden nog niet ondersteund in deze weergave.';
					$toadd["src"] = $newLoc;
				}
				else {
					// er is geen afbeelding geupload om te gebruiken
					break;
				}
			}
			else if ($_POST["type"] == "video") {
				if (isset($_FILES["video"])) {
					if ($_FILES["video"]["error"] === UPLOAD_ERR_OK) {
						if ($_FILES["video"]["size"] > 0) {
							$temp = explode(".", $_FILES["video"]["name"]);
							$newLoc = "content/".$toadd["id"].".".end($temp);
							if (move_uploaded_file($_FILES["video"]["tmp_name"], $newLoc) === true) {
								$fileMoved = true;
								$otherLoc = $newLoc;
								$extension = $temp;
								$toadd["content"] = 'Videos worden nog niet ondersteund in deze weergave.';
								$toadd["src"] = $newLoc;
							}
							else {
								// er ging iets mis bij het verplaatsen van het geuploadde bestand naar de juiste locatie
								break;
							}
						}
						else {
							// bestandsgrootte is 0, is het bestand correct geupload?
							break;
						}
					}
					else {
						// er ging iets fout bij het uploaden
						break;
					}
				}
				else if ($fileMoved) {
					$newLoc = "content/".$toadd["id"].".".end($extension);
					copy($otherLoc, $newLoc);
					$toadd["content"] = 'Afbeeldingen worden nog niet ondersteund in deze weergave.';
					$toadd["src"] = $newLoc;
				}
				else {
					// er is geen video geupload om te gebruiken
					break;
				}
			}
			else if ($_POST["type"] == "zermelo") {
				$toadd["title"] = "Roosterwijzigingen voor vandaag";
				$toadd["content"] = "Laden...";
				$toadd["src"] = null;
			}
			else {
				// onbekend type
				break;
			}
			array_push($todonots["messages"], $toadd);
			// voeg de melding toe aan de JSON
			$todonots = json_encode($todonots, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
			file_put_contents($screens[$todo]["file"], $todonots);
		}
		
		// refresh zonder POST gegevens
		header("Location: portal.php?view=".$_SESSION["view"]);
		die();
	}
	else if (isset($_POST["editnot"]) && !empty($_POST["edit-id"])) {
		$id = explode("-", $_POST["edit-id"]);
		// todo is het id van het scherm
		$todo = $id[0];
		// toedit is het id van de te editen melding
		$toedit = implode("-", $id);
		
		// verkrijg alle meldingen voor het scherm en cycle hier doorheen
		$todonots = json_decode(file_get_contents($screens[$todo]["file"]), true);
		for ($i = 0; $i < count($todonots["messages"]); $i++) {
			// controleer het id van deze melding
			if ($todonots["messages"][$i]["id"] == $toedit) {
				// als de id van deze melding overeen komt met dat van die we willen editen, edit de melding en stop de loop
				
				if (isset($_POST["edit-start-date"]) && !empty($_POST["edit-start-date"])) {
					$startDate = strtotime($_POST["edit-start-date"]);
					if ($startDate !== false) {
						$todonots["messages"][$i]["start-date"] = $startDate;
					}
					else {
						$todonots["messages"][$i]["start-date"] = null;
					}
				}
				else {
					$todonots["messages"][$i]["start-date"] = null;
				}
				
				if (isset($_POST["edit-end-date"]) && !empty($_POST["edit-end-date"])) {
					$endDate = strtotime($_POST["edit-end-date"]);
					if ($endDate !== false) {
						$todonots["messages"][$i]["end-date"] = $endDate;
					}
					else {
						$todonots["messages"][$i]["end-date"] = null;
					}
				}
				else {
					$todonots["messages"][$i]["end-date"] = null;
				}
				
				if ($todonots["messages"][$i]["type"] == "default") {
					$todonots["messages"][$i]["title"] = $_POST["edit-title"];
					if (!empty($_POST["edit-content"])) {
						// vervang line breaks met html line breaks
						$todonots["messages"][$i]["content"] = str_replace(PHP_EOL, "<br>", $_POST["edit-content"]);
					}
				}
				$todonots["messages"][$i]["modified"] = time();
				break;
			}
		}
		$todonots = json_encode($todonots, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		file_put_contents($screens[$todo]["file"], $todonots);
		
		// refresh zonder POST gegevens
		header("Location: portal.php?view=".$_SESSION["view"]);
		die();
	}
	
	if (!empty($_POST)) {
		// refresh zonder POST gegevens
		header("Location: portal.php?view=".$_SESSION["view"]);
		die();
	}
	
	function secondsToTime($inputSeconds) {
		$secondsInAMinute = 60;
		$secondsInAnHour = 60 * $secondsInAMinute;
		$secondsInADay = 24 * $secondsInAnHour;

		// Extract days
		$days = floor($inputSeconds / $secondsInADay);

		// Extract hours
		$hourSeconds = $inputSeconds % $secondsInADay;
		$hours = floor($hourSeconds / $secondsInAnHour);

		// Extract minutes
		$minuteSeconds = $hourSeconds % $secondsInAnHour;
		$minutes = floor($minuteSeconds / $secondsInAMinute);

		// Extract the remaining seconds
		$remainingSeconds = $minuteSeconds % $secondsInAMinute;
		$seconds = ceil($remainingSeconds);

		// Format and return
		$timeParts = [];
		$sections = [
			'dag' => (int)$days,
			'uur' => (int)$hours,
			'minuten' => (int)$minutes
		];

		foreach ($sections as $name => $value){
			if ($value > 0){
				$timeParts[] = $value. ' '.$name.(($value == 1 || $name == "minuten" || $name == "uur") ? '' : 'en');
				break;
			}
		}

		return implode(', ', $timeParts);
	}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<title>Meldingen Portaal</title>
	<style><?PHP readfile("styles.css"); ?></style>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="icon" type="image/ico" href="favicon.ico" /> 
</head>
<body onload="document.getElementById('loading').style.display = 'none';">
	<header>
		<h1 id="pagetitle">Alle meldingen voor alle schermen</h1>
		<div id="pageoptions">
			<div id="digitalocean" title="DigitalOcean Control Panel" onclick="window.open('https://cloud.digitalocean.com/account/billing?i=f9959d');"><img src="import/digitalocean.png" style="height: 20px;"/></div>
			<div id="github" title="GitHub-repository" onclick="window.open('https://github.com/FreekBes/damstede-meldingen');"><img src="import/github.png" style="height: 20px;"/></div>
			<div id="terminal" title="Linux-terminal openen" onclick="window.open('https://' + window.location.host + ':12320');">&#x2265;</div>
			<div id="webmin" title="Webmin (serverbeheer) openen" onclick="window.open('https://' + window.location.host + ':10000');">&#x2699;</div>
			<?PHP if ($_SESSION["view"] == "previews") { ?>
			<div id="switchview" title="Tabelweergave gebruiken" onclick="window.location.href='?view=table';">&#8862;</div>
			<?PHP } else { ?>
			<div id="switchview" title="Miniatuurweergave gebruiken" onclick="window.location.href='?view=previews';">&#9648;</div>
			<?PHP } ?>
			<div id="addnotbtn" title="Nieuwe melding opstellen" onclick="showAction('notadder');">+</div>
		</div>
	</header>
	<div id="content">
		<?PHP
			foreach($screens as $screen) {
				if (isset($screen["last-usage"]) && !empty($screen["last-usage"]) && $screen["last-usage"] < time() - 600) {
					?>
					<div class="warning">Het scherm <?PHP echo $screen["name"]; ?> op locatie <?PHP echo $screen["location"]; ?> is al <?PHP echo secondsToTime(time() - $screen["last-usage"]); ?> inactief! Staat het wel aan?</div>
					<?PHP
				}
			}
		?>
		<?PHP if ($_SESSION["view"] == "table") { ?>
			<table class="notstable">
				<!-- headers staan in de sorting functie! -->
				<?PHP
					$totalNots = 0;
					foreach($screens as $screen) {
						// voor elk scherm weergeef alle meldingen
						$nots = json_decode(file_get_contents($screen["file"]), true);
						foreach($nots["messages"] as $msg) {
							$msg["extra_class"] = "";
							if (!empty($msg["start-date"])) {
								if ($msg["start-date"] > time()) {
									$msg["extra_class"] = " upcoming";
								}
							}
							if (!empty($msg["end-date"])) {
								if ($msg["end-date"] + 86400 < time()) {
									$msg["extra_class"] = " overdue";
								}
							}
							?>
							<tr class="notsrow<?PHP echo $msg["extra_class"]; ?>">
								<td class="center"><?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)</td>
								<td class="center"><?PHP echo date("d-m-Y H:i", $msg["added"]); ?></td>
								<td class="center"><?PHP echo date("d-m-Y H:i", $msg["modified"]); ?></td>
								<td class="center"><?PHP if (!empty($msg["start-date"])) { echo date("d-m-Y", $msg["start-date"]); } else { echo "Niet ingesteld"; } ?></td>
								<td class="center"><?PHP if (!empty($msg["end-date"])) { echo date("d-m-Y", $msg["end-date"]); } else { echo "Niet ingesteld"; } ?></td>
								<?PHP if ($msg["type"] == "default") { ?>
									<td class="center nowrap"><a class="notoption" title="Melding bewerken" href="javascript:void()" onclick="setEditFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<?PHP echo addslashes(preg_replace("/\r|\n/", "", str_replace('"', '&quot;', $msg["content"]))); ?>', '<?PHP echo date('Y-m-d', intval($msg["start-date"])); ?>', '<?PHP echo date('Y-m-d', intval($msg["end-date"])); ?>'); showAction('noteditor');">&#x270e;</a><a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<?PHP echo addslashes(preg_replace("/\r|\n/", "", str_replace('"', '&quot;', $msg["content"]))); ?>'); showAction('notdeletor');">&#x1f5d1;</a></td>
								<?PHP } else if ($msg["type"] == "image") { ?>
									<td class="center nowrap"><a class="notoption" title="Melding bewerken" href="javascript:void()" onclick="setEditFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', null, '<?PHP echo date('Y-m-d', intval($msg["start-date"])); ?>', '<?PHP echo date('Y-m-d', intval($msg["end-date"])); ?>'); showAction('noteditor');">&#x270e;</a><a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<img src=\'<?PHP echo $msg["src"]; ?>\' style=\'max-width: 420px; max-height: 420px;\' />'); showAction('notdeletor');">&#x1f5d1;</a></td>
								<?PHP } else if ($msg["type"] == "video") { ?>
									<td class="center nowrap"><a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<video controls muted src=\'<?PHP echo $msg["src"]; ?>\' style=\'max-width: 420px; max-height: 420px;\' />'); showAction('notdeletor');">&#x1f5d1;</a></td>
								<?PHP } else if ($msg["type"] == "zermelo") { ?>
									<td class="center nowrap"><a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', 'Geen voorbeeld om weer te geven'); showAction('notdeletor');">&#x1f5d1;</a></td>
								<?PHP } ?>
								<?PHP if ($msg["type"] == "default" || $msg["type"] == "image" || $msg["type"] == "zermelo") { ?>
									<td><?PHP echo $msg["title"]; ?></td>
								<?PHP } else { ?>
									<td></td>
								<?PHP } ?>
								<?PHP if ($msg["type"] == "default") { ?>
									<td><?PHP echo $msg["content"]; ?></td>
								<?PHP } else if ($msg["type"] == "image") { ?>
									<td><img src="<?PHP echo $msg["src"]; ?>" onclick="window.open('<?PHP echo $msg["src"]; ?>')" style="cursor: zoom-in;" /></td>
								<?PHP } else if ($msg["type"] == "video") { ?>
									<td><video src="<?PHP echo $msg["src"]; ?>" controls muted>Video kan niet worden weergegeven</video></td>
								<?PHP } else if ($msg["type"] == "zermelo") { ?>
									<td>Geen voorbeeld om weer te geven</td>
								<?PHP } ?>
							</tr>
							<?PHP
							$totalNots += 1;
						}
					}
					
					if ($totalNots < 1) {
						?>
						<tr class="table-info">
							<td colspan="6" style="text-align: center; padding: 36px; color: #A1A1A1;">Geen meldingen om weer te geven. Voeg een melding toe door op de + rechts bovenin te klikken.</td>
						</tr>
						<?PHP
					}
				?>
			</table>
			<script>
			function sortTable(parent, sortProperty) {
				console.log("Tabel sorteren...");
				var previews = parent.getElementsByClassName("notsrow");
				previews = Array.prototype.slice.call(previews,0)
				previews.sort(function(a,b) {
					return (a[sortProperty].length - b[sortProperty].length);
				});
				return previews;
			}
			
			var previewLists = document.getElementsByClassName("notstable");
			var thisPreviews = [];
			for (var p = 0; p < previewLists.length; p++) {
				if (previewLists[p].getElementsByClassName("table-info").length < 1) {
					thisPreviews = sortTable(previewLists[p], "className");
					console.log(thisPreviews);
					previewLists[p].innerHTML = "<tr><th>Scherm</th><th>Toegevoegd</th><th>Laatst gewijzigd</th><th>Startdatum</th><th>Einddatum</th><th>Opties</th><th>Melding titel</th><th>Melding content</th></tr>";
					for (var q = 0; q < thisPreviews.length; q++) {
						previewLists[p].appendChild(thisPreviews[q]);
					}
				}
			}
			</script>
		<?PHP } else if ($_SESSION["view"] == "previews") { ?>
			<ul>
				<?PHP
					$totalNots = 0;
					foreach($screens as $screen) {
						?>
							<div class="preview-list-title"><?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)</div>
							<ul class="preview-list">
							<?PHP
								// voor elk scherm weergeef alle meldingen
								$nots = json_decode(file_get_contents($screen["file"]), true);
								$thisNots = 0;
								foreach($nots["messages"] as $msg) {
									$msg["extra_class"] = "";
									if (!empty($msg["start-date"])) {
										if ($msg["start-date"] > time()) {
											$msg["extra_class"] = " upcoming";
										}
									}
									if (!empty($msg["end-date"])) {
										if ($msg["end-date"] + 86400 < time()) {
											$msg["extra_class"] = " overdue";
										}
									}
									?>
									<li class="preview<?PHP echo $msg["extra_class"]; ?>" title="<?PHP echo $screen["name"]." (".($thisNots+1)."/".count($nots["messages"]).")"; ?>">
										<iframe scrolling="no" src="meldingen.php?screen=<?PHP echo $screen["id"]; ?>&num=<?PHP echo $thisNots; ?>&no&preview">Previews worden niet ondersteunt in deze browser. <a href="?view=table">Maak in plaats daarvan gebruik van de tabelweergave.</a></iframe>
										<div class="preview-options">
											<table class="preview-info">
												<tr>
													<th>Toegevoegd</th>
													<td><?PHP echo date("d-m-Y H:i", $msg["added"]); ?></td>
												</tr>
												<tr>
													<th>Laatst gewijzigd</th>
													<td><?PHP echo date("d-m-Y H:i", $msg["modified"]); ?></td>
												</tr>
												<tr>
													<th>Startdatum</th>
													<td><?PHP if (!empty($msg["start-date"])) { echo date("d-m-Y", $msg["start-date"]); } else { echo "niet ingesteld"; } ?></td>
												</tr>
												<tr>
													<th>Einddatum</th>
													<td><?PHP if (!empty($msg["end-date"])) { echo date("d-m-Y", $msg["end-date"]); } else { echo "niet ingesteld"; } ?></td>
												</tr>
											</table>
											<?PHP if ($msg["type"] == "default") { ?>
												<a class="notoption" title="Melding bewerken" href="javascript:void()" onclick="setEditFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<?PHP echo addslashes(preg_replace("/\r|\n/", "", str_replace('"', '&quot;', $msg["content"]))); ?>', '<?PHP echo date('Y-m-d', intval($msg["start-date"])); ?>', '<?PHP echo date('Y-m-d', intval($msg["end-date"])); ?>'); showAction('noteditor');">&#x270e;</a><a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<?PHP echo addslashes(preg_replace("/\r|\n/", "", str_replace('"', '&quot;', $msg["content"]))); ?>'); showAction('notdeletor');">&#x1f5d1;</a>
											<?PHP } else if ($msg["type"] == "image") { ?>
												<a class="notoption" title="Melding bewerken" href="javascript:void()" onclick="setEditFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', null, '<?PHP echo date('Y-m-d', intval($msg["start-date"])); ?>', '<?PHP echo date('Y-m-d', intval($msg["end-date"])); ?>'); showAction('noteditor');">&#x270e;</a><a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<img src=\'<?PHP echo $msg["src"]; ?>\' style=\'max-width: 420px; max-height: 420px;\' />'); showAction('notdeletor');">&#x1f5d1;</a>
											<?PHP } else if ($msg["type"] == "video") { ?>
												<a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', '<video controls muted src=\'<?PHP echo $msg["src"]; ?>\' style=\'max-width: 420px; max-height: 420px;\' />'); showAction('notdeletor');">&#x1f5d1;</a>
											<?PHP } else if ($msg["type"] == "zermelo") { ?>
												<td class="center nowrap"><a class="notoption" title="Melding verwijderen" href="javascript:void()" onclick="setDeleteFormValues('<?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)', '<?PHP echo $msg["id"]; ?>', '<?PHP echo addslashes(str_replace('"', '&quot;', $msg["title"])); ?>', 'Geen voorbeeld om weer te geven'); showAction('notdeletor');">&#x1f5d1;</a></td>
											<?PHP } ?>
										</div>
									</li>
									<?PHP
									$totalNots += 1;
									$thisNots += 1;
								}
								
								if ($thisNots < 1) {
									?>
									<li class="preview-list-info">Geen meldingen om weer te geven. Voeg een melding toe door op de + rechts bovenin te klikken.</li>
									<?PHP
								}
							?>
							</ul>
						<?PHP
					}
				?>
			</ul>
			<script>
			function sortPreviews(parent, sortProperty) {
				console.log("Previews sorteren...");
				var previews = parent.getElementsByClassName("preview");
				previews = Array.prototype.slice.call(previews,0)
				previews.sort(function(a,b) {
					return (a[sortProperty].length - b[sortProperty].length);
				});
				return previews;
			}
			
			var previewLists = document.getElementsByClassName("preview-list");
			var thisPreviews = [];
			for (var p = 0; p < previewLists.length; p++) {
				if (previewLists[p].getElementsByClassName("preview-list-info").length < 1) {
					thisPreviews = sortPreviews(previewLists[p], "className");
					console.log(thisPreviews);
					previewLists[p].innerHTML = "";
					for (var q = 0; q < thisPreviews.length; q++) {
						previewLists[p].appendChild(thisPreviews[q]);
					}
				}
			}
			</script>
		<?PHP } else {
			header("Location: portal.php?view=table");
			die();
		} ?>
	</div>
	
	<script>
	function showAction(name) {
		var actions = document.getElementsByClassName("action");
		for (var i = 0; i < actions.length; i++) {
			actions[i].style.display = "none";
		}
		
		var action = document.getElementById(name);
		action.style.display = "table";
	}
	
	function hideAction(elem) {
		var actionName = elem.getAttribute("data-action");
		
		var action = document.getElementById(actionName);
		action.style.display = "none";
	}
	
	function setEditFormValues(screenName, id, title, content, startDate, endDate) {
		document.getElementById("edit-screen").innerHTML = screenName;
		document.getElementById("edit-id").value = id;
		document.getElementById("edit-title").value = title;
		if (content == null) {
			document.getElementById("edit-content").disabled = true;
			document.getElementById("edit-content").innerHTML = "";
			document.getElementById("edit-content").value = "";
			document.getElementById("edit-content").setAttribute("placeholder", "Niet aanpasbaar voor dit type bericht. Als je het bericht wilt aanpassen, zul je het moeten verwijderen en daarna opnieuw moeten toevoegen.");
		}
		else {
			document.getElementById("edit-content").disabled = false;
			document.getElementById("edit-content").innerHTML = content;
			document.getElementById("edit-content").value = content.split("<br>").join("\n");
			document.getElementById("edit-content").setAttribute("placeholder", "Het bericht wat moet worden weergegeven op het scherm (onder de titel)");
		}
		if (startDate == null || startDate == "null" || startDate == "1970-01-01") {
			document.getElementById("edit-start-date").value = "";
		}
		else {
			document.getElementById("edit-start-date").value = startDate;
		}
		if (endDate == null || endDate == "null" || endDate == "1970-01-01") {
			document.getElementById("edit-end-date").value = "";
		}
		else {
			document.getElementById("edit-end-date").value = endDate;
		}
	}
	
	function setDeleteFormValues(screenName, id, title, content) {
		document.getElementById("del").value = id;
		document.getElementById("deletor-screen").innerHTML = screenName;
		document.getElementById("deletor-title").innerHTML = title;
		document.getElementById("deletor-content").innerHTML = '<small>'+content+'</small>';
	}
	</script>
	
	<div class="action" id="notadder" style="display: none;">
		<div class="inneraction">
			<div class="actioncontent">
				<form action="portal.php?view=<?PHP echo $_SESSION["view"]; ?>" method="post" target="_self" enctype="multipart/form-data" accept-charset="utf-8" autocomplete="off" name="addform">
					<div class="actionheader">Voeg een nieuwe melding toe</div>
					<div class="actionclose" data-action="notadder" onclick="hideAction(this);">&#x2716;</div>
					<table class="actiontable">
						<tr>
							<th>Berichttype</th>
							<td>
								<input type="radio" id="default" name="type" value="default" checked /><label for="default">Tekst</label>
								<input type="radio" id="image" name="type" value="image" /><label for="image">Afbeelding</label>
								<input type="radio" id="video" name="type" value="video" /><label for="video">Video</label>
								<input type="radio" id="zermelo" name="type" value="zermelo" /><label for="zermelo">Roosterwijzigingen</label>
							</td>
							<script>
							var notType = document.getElementsByName("type");
							for (i = 0; i < notType.length; i++) {
								notType[i].addEventListener("change", function(event) {
									var elem = event.target;
									var newType = elem.value;
									var defaultElems = document.getElementsByClassName("nottype-default");
									var imageElems = document.getElementsByClassName("nottype-image");
									var videoElems = document.getElementsByClassName("nottype-video");
									var zermeloElems = document.getElementsByClassName("nottype-zermelo");
									for (j = 0; j < defaultElems.length; j++) {
										defaultElems[j].style.display = "none";
									}
									for (j = 0; j < imageElems.length; j++) {
										imageElems[j].style.display = "none";
									}
									for (j = 0; j < videoElems.length; j++) {
										videoElems[j].style.display = "none";
									}
									for (j = 0; j < zermeloElems.length; j++) {
										zermeloElems[j].style.display = "none";
									}
									switch (newType) {
										case "default": {
											for (j = 0; j < defaultElems.length; j++) {
												defaultElems[j].style.display = "table-row";
											}
											break;
										}
										case "image": {
											for (j = 0; j < imageElems.length; j++) {
												imageElems[j].style.display = "table-row";
											}
											break;
										}
										case "video": {
											for (j = 0; j < videoElems.length; j++) {
												videoElems[j].style.display = "table-row";
											}
											break;
										}
										case "zermelo": {
											for (j = 0; j < zermeloElems.length; j++) {
												zermeloElems[j].style.display = "table-row";
											}
											break;
										}
									}
								});
							}
							</script>
						</tr>
						<tr class="nottype-zermelo" style="display: none;">
							<th>Informatie</th>
							<td class="nolh">Weergeeft als melding de roosterwijzigingen voor de desbetreffende locatie. Mede mogelijk gemaakt door Zermelo.</td>
						</tr>
						<tr class="nottype-default">
							<th>Titel</th>
							<td><input type="text" id="title" name="title" autocomplete="off" placeholder="De titel voor deze melding" size="65" /></td>
						</tr>
						<tr class="nottype-default">
							<th>Bericht</th>
							<td><textarea id="content" name="content" placeholder="Het bericht wat moet worden weergegeven op het scherm (onder de titel)" rows="5" cols="60"></textarea></td>
						</tr>
						<tr class="nottype-image" style="display: none;">
							<th>Titel</th>
							<td><input type="text" id="title-image" name="title-image" autocomplete="off" placeholder="Titels kunnen niet worden ingesteld voor afbeeldingen" size="65" disabled /></td>
						</tr>
						<tr class="nottype-image" style="display: none;">
							<th>Afbeelding</th>
							<td><input type="file" name="image" id="image" accept="image/*" /></td>
						</tr>
						<tr class="nottype-video" style="display: none;">
							<th>Titel</th>
							<td><input type="text" id="title-video" name="title-video" autocomplete="off" placeholder="Titels kunnen niet worden ingesteld voor videomeldingen" size="65" disabled /></td>
						</tr>
						<tr class="nottype-video" style="display: none;">
							<th>Video</th>
							<td><input type="file" name="video" id="video" accept="video/*" /></td>
						</tr>
						<tr>
							<th>Voor schermen</th>
							<td class="nolh">
								<?PHP foreach($screens as $screen) { ?>
								<input type="checkbox" name="screens[]" value="<?PHP echo $screen["id"]; ?>" id="<?PHP echo $screen["id"]; ?>" /><label for="<?PHP echo $screen["id"]; ?>"><?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)</label><br>
								<?PHP } ?>
							</td>
						</tr>
						<tr>
							<td class="withpadding" colspan="2"><small><i>Onderstaande datums zijn niet verplicht. Bij een lege startdatum wordt de melding direct weergegeven, bij een lege einddatum blijft de melding staan tot deze wordt verwijderd.</i></small></td>
						</tr>
						<tr>
							<th>Startdatum</th>
							<td><input type="date" name="start-date" id="start-date" /></td>
						</tr>
						<tr>
							<th>Einddatum</th>
							<td><input type="date" name="end-date" id="end-date" /></td>
						</tr>
					</table>
					<div class="actionbuttons">
						<input class="button extra" type="button" value="Annuleren" data-action="notadder" onclick="hideAction(this);" />
						<input class="button" type="submit" value="Melding toevoegen" name="addnot" onclick="document.getElementById('loading').style.display = 'table';" />
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<div class="action" id="noteditor" style="display: none;">
		<div class="inneraction">
			<div class="actioncontent">
				<form action="portal.php?view=<?PHP echo $_SESSION["view"]; ?>" method="post">
					<div class="actionheader">Melding bewerken voor <span id="edit-screen">een onbekend scherm</span></div>
					<div class="actionclose" data-action="noteditor" onclick="hideAction(this);">&#x2716;</div>
					<input type="hidden" name="edit-id" id="edit-id" value="" />
					<table class="actiontable">
						<tr>
							<th>Titel</th>
							<td><input type="text" id="edit-title" name="edit-title" autocomplete="off" placeholder="De titel voor deze melding" size="65" value="" /></td>
						</tr>
						<tr>
							<th>Bericht</th>
							<td><textarea id="edit-content" name="edit-content" placeholder="Het bericht wat moet worden weergegeven op het scherm (onder de titel)" rows="5" cols="60"></textarea></td>
						</tr>
						<tr>
							<td class="withpadding" colspan="2"><small><i>Onderstaande datums zijn niet verplicht. Bij een lege startdatum wordt de melding direct weergegeven, bij een lege einddatum blijft de melding staan tot deze wordt verwijderd.</i></small></td>
						</tr>
						<tr>
							<th>Startdatum</th>
							<td><input type="date" name="edit-start-date" id="edit-start-date" /></td>
						</tr>
						<tr>
							<th>Einddatum</th>
							<td><input type="date" name="edit-end-date" id="edit-end-date" /></td>
						</tr>
					</table>
					<div class="actionbuttons">
						<input class="button extra" type="button" value="Annuleren" data-action="noteditor" onclick="hideAction(this);" />
						<input class="button" type="submit" value="Opslaan" name="editnot" onclick="document.getElementById('loading').style.display = 'table';" />
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<div class="action" id="notdeletor" style="display: none;">
		<div class="inneraction">
			<div class="actioncontent">
				<form action="portal.php?view=<?PHP echo $_SESSION["view"]; ?>" method="get">
					<div class="actionheader">Weet je zeker dat je de volgende melding wilt verwijderen?</div>
					<div class="actionclose" data-action="notdeletor" onclick="hideAction(this);">&#x2716;</div>
					<input type="hidden" name="del" id="del" value="" />
					<table class="actiontable">
						<tr>
							<th class="withpadding">Scherm</th>
							<td class="withpadding" id="deletor-screen"></td>
						</tr>
						<tr>
							<th class="withpadding">Titel</th>
							<td class="withpadding" id="deletor-title"></td>
						</tr>
						<tr>
							<th class="withpadding">Bericht</th>
							<td class="withpadding" id="deletor-content"></td>
						</tr>
					</table>
					<div class="actionbuttons">
						<input class="button extra" type="button" value="Annuleren" data-action="notdeletor" onclick="hideAction(this);" />
						<input class="button" type="submit" value="Verwijderen" name="delnot" onclick="document.getElementById('loading').style.display = 'table';" />
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<div id="loading" style="display: table; width: 100%; height: 100%; position: fixed; top: 0px; left: 0px; right: 0px; bottom: 0px; background-color: rgba(0,0,0,0.8);">
		<div style="display: table-cell; vertical-align: middle;">
			<div style="margin-left: auto; margin-right: auto; text-align: center;">
				<div id="spinner"></div>
			</div>
		</div>
	</div>
	
	<script>
	// log uit na 10 minuten
	var logoutTimer = setTimeout(function() {
		window.location.href = "/logout.php?by=auto";
	}, 600000);
	function resetLogoutTimer() {
		console.log("Logout timeout resetten...");
		clearTimeout(logoutTimer);
		logoutTimer = null;
		logoutTimer = setTimeout(function() {
			window.location.href = "/logout.php?by=auto";
		}, 600000);
	}
	document.onkeydown = resetLogoutTimer;
	document.onmousedown = resetLogoutTimer;
	</script>
	</body>
</html>