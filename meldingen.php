<?PHP
	session_start();

	function error_found(){
		$_SESSION["error_last_page"] = $_SERVER['REQUEST_URI'];
		header("Location: error.php", 301);
		exit();
	}
	set_error_handler('error_found', E_ALL);

	// verkrijg alle beschikbare schermen
	$screens = json_decode(file_get_contents("screens.json"), true);
	
	if (!isset($_GET["screen"]) || empty($_GET["screen"])) {
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<title>Selecteer weer te geven meldingen</title>
	<link rel="stylesheet" href="basicstyles.css" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="icon" type="image/ico" href="favicon.ico" /> 
</head>
<body>
	<form action="meldingen.php" method="get">
		<h1>Meldingen Damstede Lyceum</h1>
		<h3>Welke meldingen moeten worden weergegeven op dit scherm?</h3>
		<select id="screen" name="screen" required>
			<option value="" selected disabled>Kies een optie...</option>
			<optgroup label="Schermen">
				<?PHP
					foreach($screens as $screen) {
						echo '<option value="'.$screen["id"].'">Alle meldingen voor '.$screen["name"].' ('.$screen["location"].')</option>';
					}
				?>
			</optgroup>
			<optgroup label="Overig">
				<option value="all">Alle meldingen voor elk van de bovenstaande schermen</option>
			</optgroup>
		</select>
		<input type="submit" value="Verder" />
		<br><br><small style="font-size: 11px;"><a style="color: #777777;" href="index.php">Terug naar het overzicht</a></small>
	</form>
</body>
</html>
<?PHP } else { ?>
<?PHP
	if (!isset($screens[$_GET["screen"]]) && $_GET["screen"] != "all") {
		// scherm niet gevonden
		echo "Scherm niet gevonden. Klik <a href='meldingen.php'>hier</a> om terug te gaan.";
		error_found();
		die();
	}
	
	if ($_GET["screen"] != "all") {
		// verkrijg het geselecteerde scherm
		$screen = $screens[$_GET["screen"]];
		
		if (!isset($_GET["preview"])) {
			// update laatst weergegeven waarde in screens.json
			$screens[$_GET["screen"]]["last-usage"] = time();
			file_put_contents("screens.json", json_encode($screens, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
		}
		
		$file = json_decode(file_get_contents($screen["file"]), true);
		$nots = $file["messages"];
	}
	else {
		$screenKeys = array_keys($screens);
		$screen = null;
		$file = null;
		$nots = array();
		for($i = 0; $i < count($screenKeys); $i++) {
			$screen = $screens[$screenKeys[$i]];
			$file = json_decode(file_get_contents($screen["file"]), true);
			for ($j = 0; $j < count($file["messages"]); $j++) {
				array_push($nots, $file["messages"][$j]);
			}
		}
		$screen["id"] = "all";
		$screen["name"] = "Alle meldingen";
		$screen["location"] = "Damstede Lyceum";
	}
	
	if (!isset($_GET["preview"])) {
		$c = count($nots);
		for ($i = 0; $i < $c; $i++) {
			if (!empty($nots[$i]["start-date"])) {
				if ($nots[$i]["start-date"] > time()) {
					$nots[$i]["underdue"] = true;
					unset($nots[$i]);
					continue;
				}
			}
			if (!empty($nots[$i]["end-date"])) {
				if ($nots[$i]["end-date"] + 86400 < time()) {
					$nots[$i]["overdue"] = true;
					unset($nots[$i]);
					continue;
				}
			}
		}
		$nots = array_values($nots);
	}
	
	$num = 0;
	if (isset($_GET["num"]) && intval($_GET["num"]) >= 0) {
		// verkrijg de weer te geven melding
		$num = intval($_GET["num"]);
	}
	else {
		header("Location: meldingen.php?screen=".$_GET["screen"]."&num=0");
		die();
	}
	
	if ($num != 0 && !($num >= 0 && $num < count($nots))) {
		// melding bestaat niet (te weinig meldingen?)
		// bij nul meldingen wordt dit niet uitgevoerd anders krijg je een infinite loop
		header("Location: meldingen.php?screen=".$_GET["screen"]."&num=0");
		die();
	}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<title><?PHP echo $screen["name"]; ?> (<?PHP echo $screen["location"]; ?>)</title>
	<script src='import/nprogress.js'></script>
	<link rel='stylesheet' href='import/nprogress.css' />
	<style><?PHP readfile("meldingen.css"); ?></style>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="icon" type="image/ico" href="favicon.ico" />
	<?PHP if (!isset($_GET["preview"])) { ?>
		<meta http-equiv="refresh" content="300" />
	<?PHP } ?>
	<script src="import/jquery-3.3.1.min.js"></script>
	<script>
	// Determines if the passed element is overflowing its bounds, either vertically or horizontally.
	// Will temporarily modify the "overflow" style to detect this if necessary.
	function checkOverflow(el) {
		var curOverflow = el.style.overflow;

		if (!curOverflow || curOverflow === "visible") {
			el.style.overflow = "hidden";
		}

		var isOverflowing = el.clientWidth < el.scrollWidth || el.clientHeight < el.scrollHeight;
		el.style.overflow = curOverflow;

		return isOverflowing;
	}
	</script>
</head>
<body onload="startCountdown();" style="<?PHP if (isset($_GET["preview"])) { echo "zoom: 0.26;"; } ?>">
	<?PHP if (count($nots) > 0) { ?>
		<?PHP
			$msg = $nots[$num];
		?>
		<script>
			console.log(JSON.parse(`<?PHP echo json_encode($msg, JSON_UNESCAPED_UNICODE); ?>`.replace(/\s/g, ' ')));
		</script>
		<script>
			var num = <?PHP echo $num; ?>;
			var total = <?PHP echo count($nots); ?>;
			<?PHP
				if ($msg["type"] != "image") {
					$dur = round((strlen($msg["title"]) + strlen($msg["content"])) * 0.035);
					if ($dur < 10) {
						// melding minimaal 3 seconden weergeven
						$dur = 10;
					}
				}
				else {
					$dur = 20;
				}
			?>
			var dur = <?PHP echo $dur; ?>;
			// configureer NProgress
			// speed is de duratie van de animatie om de progressie aan te passen, dit gaat direct naar 100%
			// dus stellen we de duratie in op de tijd dat deze melding zichtbaar is zodat de progressbar bijhoudt
			NProgress.configure({
				minimum: 0.0,
				easing: 'linear',
				speed: dur * 1000,
				showSpinner: false
			});
		</script>
		<div class="msgcontainer<?PHP if ($msg["type"] != "default") { echo " centered"; } ?>">
			<?PHP if ($msg["type"] == "default") { ?>
				<h1 id="not-title"><?PHP echo $msg["title"]; ?></h1>
				<div id="content">
					<p><?PHP echo $msg["content"]; ?></p>
				</div>
			<?PHP } else if ($msg["type"] == "image") { ?>
				<img class="msgimage" src="<?PHP echo $msg["src"]; ?>" alt="Afbeelding kan niet worden weergegeven" />
			<?PHP } else if ($msg["type"] == "video") { ?>
				<video class="msgvideo" id="video" src="<?PHP echo $msg["src"]; ?>" autoplay muted preload="auto"<?PHP if (isset($_GET["no"])) { echo " loop"; } ?>>Video kan niet worden afgespeeld</video>
			<?PHP } else if ($msg["type"] == "zermelo") { ?>
				<h1 id="not-title"><?PHP echo $msg["title"]; ?></h1>
				<div id="content">
					<div id="loading" style="display: table; width: 100%; height: 500px;">
						<div style="display: table-cell; vertical-align: middle;">
							<div style="margin-left: auto; margin-right: auto; text-align: center;">
								<div id="spinner"></div>
							</div>
						</div>
					</div>
				</div>
			<?PHP } ?>
		</div>
		<div class="msgcounter"><?PHP echo ($num + 1)."/".count($nots); ?></div>
		<div id="msgoverflow" style="display: none;"></div>
		<?PHP if ($msg["type"] != "video" && $msg["type"] != "zermelo" && !isset($_GET["no"])) { ?>
			<!-- voeg &no toe aan de URL om niet automatisch naar de volgende melding te gaan -->
			<script>
				function startCountdown() {
					NProgress.start();
					// stel progressie in op 100%, door de animatie duurt het even voordat dat daadwerkelijk zichtbaar is
					NProgress.set(1);
					setTimeout(function() {
						NProgress.done(true);
						// ga naar de volgende melding
						window.location.href = window.location.href.split("&num")[0] + "&num=" + (num+1);
					}, dur * 1000);
					
					if (checkOverflow(document.getElementsByTagName("body")[0])) {
						// als de tekst niet volledig op het scherm past, moet er automatisch worden gescrolld.
						console.log("Content moet automatisch scrollen!");
						
						setTimeout(function() {
							// het scrollen gebeurt na 2 seconden.
							var paddingHeight = $("#not-title").outerHeight();
							document.getElementById("content").style.padding = paddingHeight+"px 0px "+(paddingHeight * 0.75)+"px 0px";
							document.getElementById("not-title").style.position = "fixed";
							document.getElementById("not-title").style.left = "36px";
							document.getElementById("not-title").style.right = "36px";
							document.getElementById("not-title").style.textShadow = "0 0 8px #000";
							document.getElementById("not-title").style.backgroundClip = "padding-box";
							// document.getElementById("not-title").style.background = "linear-gradient(rgba(51,51,51,1), rgba(51,51,51,0))";
							document.getElementById("not-title").style.background = "linear-gradient(rgba(51,51,51,1), rgba(51,51,51,1), rgba(51,51,51,1), rgba(51,51,51,1), rgba(51,51,51,0))";
							$('body,html').animate({scrollTop: $(document).height()}, dur * 800);
						}, 2000);
						
						document.getElementById("msgoverflow").style.display = "block";
					}
				}
			</script>
		<?PHP } else if ($msg["type"] == "video" && !isset($_GET["no"])) { ?>
			<!-- voeg &no toe aan de URL om niet automatisch naar de volgende melding te gaan -->
			<script>
			var video = document.getElementById("video");
			NProgress.configure({
				speed: 100
			});
			video.addEventListener("timeupdate", function(event) {
				// update de blauwe balk bovenin met de tijd van de video
				var curTime = video.currentTime;
				var duration = video.duration;
				var perc = curTime / duration;
				NProgress.set(perc);
			});
			video.addEventListener("stalled", function(event) {
				// video kon niet verder worden afgespeeld. Op naar de volgende melding.
				NProgress.done(true);
				// ga naar de volgende melding
				window.location.href = window.location.href.split("&num")[0] + "&num=" + (num+1);
			});
			video.addEventListener("error", function(event) {
				// video kon niet verder worden afgespeeld. Op naar de volgende melding.
				NProgress.done(true);
				// ga naar de volgende melding
				window.location.href = window.location.href.split("&num")[0] + "&num=" + (num+1);
			});
			video.addEventListener("ended", function(event) {
				// video is klaar met afspelen. Op naar de volgende melding!
				NProgress.done(true);
				// ga naar de volgende melding
				window.location.href = window.location.href.split("&num")[0] + "&num=" + (num+1);
			});
			</script>
		<?PHP } else if ($msg["type"] == "zermelo") { ?>
			<script>
			function startCountdownZ(dur) {
				<?PHP if (!isset($_GET["no"])) { ?>
				NProgress.configure({
					speed: dur * 1000
				});
				NProgress.start();
				// stel progressie in op 100%, door de animatie duurt het even voordat dat daadwerkelijk zichtbaar is
				NProgress.set(1);
				setTimeout(function() {
					NProgress.done(true);
					// ga naar de volgende melding
					window.location.href = window.location.href.split("&num")[0] + "&num=" + (num+1);
				}, dur * 1000);
				<?PHP } else { ?>
				console.log("GET no is gegeven dus we gaan niet door naar de volgende melding.");
				<?PHP } ?>
			}
			
			function pad(d) {
				return (d < 10) ? '0' + d.toString() : d.toString();
			}
			
			var xhrz = new XMLHttpRequest();
			xhrz.open('POST', 'zermelo.php?screen=<?PHP echo $screen["id"]; ?>');
			xhrz.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhrz.onload = function() {
				try {
					var response = JSON.parse(xhrz.responseText);
					// console.log(response);
					if (response["type"] == "success") {
						console.log(response["message"]);
						if (response["data"].length > 0) {
							<?PHP if ($screen["name"] != "Docentenkamer") { ?>
								// voor de docentenkamer is er een aparte roosterweergave die focust op docent ipv klas.
								var rooster = "<table class='rooster'><tr><th>Klas</th><th>Lesuur</th><th>Vak &amp; docent</th><th>Lokaal</th><th>Wijziging</th></tr>";
								var now = new Date();
								for (i = 0; i < response["data"].length; i++) {
									var lesuur = response["data"][i];
									rooster += "<tr class='";
									if (lesuur['groups'][0] != '-') {
										if (parseInt(lesuur["lastModified"]) > (now.getTime() - 3600000) / 1000) {
											rooster += " recent";
										}
										if (lesuur['changeDescription'] == "Les vervalt") {
											rooster += ' cancelled';
										}
										rooster += "'>";
										rooster += "<td class='nowrap'>";
										for (j = 0; j < lesuur["groups"].length; j++) {
											rooster += lesuur["groups"][j].toUpperCase();
											if (j != lesuur["groups"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += "</td>";
										rooster += "<td class='nowrap'>"+lesuur['startTimeSlot']+"<sup>e</sup> uur <small>(";
										var d = new Date(0);
										d.setUTCSeconds(parseInt(lesuur["start"]));
										rooster += pad(d.getHours())+":"+pad(d.getMinutes());
										rooster += ")</small></td>";
										rooster += "<td class='nowrap'>";
										for (j = 0; j < lesuur["subjects"].length; j++) {
											rooster += lesuur["subjects"][j];
											if (j != lesuur["subjects"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += " <small>van</small> ";
										for (j = 0; j < lesuur["teachers"].length; j++) {
											rooster += lesuur["teachers"][j].toUpperCase();
											if (j != lesuur["teachers"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += "</td>";
										rooster += "<td class='nowrap'>";
										for (j = 0; j < lesuur["locations"].length; j++) {
											rooster += lesuur["locations"][j].toUpperCase();
											if (j != lesuur["locations"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += "</td>";
										rooster += "<td>"+lesuur['changeDescription']+"</td>";
									}
									else {
										rooster += " class='recent'>";
										rooster += "<td colspan='5'>"+lesuur['changeDescription']+"</td>";
									}
									rooster += "</tr>";
								}
								rooster += "</table>";
							<?PHP } else { ?>
								var rooster = "<table class='rooster'><tr><th>Docent</th><th>Lesuur</th><th>Vak &amp; klas</th><th>Lokaal</th><th>Wijziging</th></tr>";
								var now = new Date();
								for (i = 0; i < response["data"].length; i++) {
									var lesuur = response["data"][i];
									rooster += "<tr class='";
									if (lesuur['teachers'][0] != '-') {
										if (parseInt(lesuur["lastModified"]) > (now.getTime() - 3600000) / 1000) {
											rooster += " recent";
										}
										if (lesuur['changeDescription'] == "Les vervalt") {
											rooster += ' cancelled';
										}
										rooster += "'>";
										rooster += "<td class='nowrap'>";
										for (j = 0; j < lesuur["teachers"].length; j++) {
											rooster += lesuur["teachers"][j].toUpperCase();
											if (j != lesuur["teachers"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += "</td>";
										rooster += "<td class='nowrap'>"+lesuur['startTimeSlot']+"<sup>e</sup> uur <small>(";
										var d = new Date(0);
										d.setUTCSeconds(parseInt(lesuur["start"]));
										rooster += pad(d.getHours())+":"+pad(d.getMinutes());
										rooster += ")</small></td>";
										rooster += "<td class='nowrap'>";
										for (j = 0; j < lesuur["subjects"].length; j++) {
											rooster += lesuur["subjects"][j];
											if (j != lesuur["subjects"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += " <small>aan</small> ";
										for (j = 0; j < lesuur["groups"].length; j++) {
											rooster += lesuur["groups"][j].toUpperCase();
											if (j != lesuur["groups"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += "</td>";
										rooster += "<td class='nowrap'>";
										for (j = 0; j < lesuur["locations"].length; j++) {
											rooster += lesuur["locations"][j].toUpperCase();
											if (j != lesuur["locations"].length - 1) {
												rooster += ', ';
											}
										}
										rooster += "</td>";
										rooster += "<td>"+lesuur['changeDescription']+"</td>";
									}
									else {
										rooster += " class='recent'>";
										rooster += "<td colspan='5'>"+lesuur['changeDescription']+"</td>";
									}
									rooster += "</tr>"
								}
								rooster += "</table>";
							<?PHP } ?>
							document.getElementById("content").innerHTML = rooster;
							var dur = response["data"].length * 1.2;
							if (dur < 15) {
								dur = 15;
							}
							startCountdownZ(dur * 1.25);
							// roosterwijzigingen blijven 25% langer in beeld staan dan standaardmeldingen.
							
							if (checkOverflow(document.getElementsByTagName("body")[0])) {
								// als de roostertabel niet volledig op het scherm past, moet er automatisch worden gescrolld.
								console.log("Content moet automatisch scrollen!");
								
								setTimeout(function() {
									// het scrollen start na 2 seconden
									var paddingHeight = $("#not-title").outerHeight();
									document.getElementById("content").style.padding = paddingHeight+"px 0px "+(paddingHeight * 0.75)+"px 0px";
									document.getElementById("not-title").style.position = "fixed";
									document.getElementById("not-title").style.left = "24px";
									document.getElementById("not-title").style.right = "24px";
									document.getElementById("not-title").style.textShadow = "0 0 8px #000";
									document.getElementById("not-title").style.backgroundClip = "padding-box";
									document.getElementById("not-title").style.background = "linear-gradient(rgba(51,51,51,1), rgba(51,51,51,0))";
									$('body,html').animate({scrollTop: $(document).height()}, dur * 1500);
									// en duurt gek genoeg anderhalf de lengte dat het scherm in beeld blijft.
									// ik weet niet of het een bug is, maar als het dezelfde waarde is dan scrollt hij te snel?
									// heel vreemd, maar zo werkt het wel ongeveer.
								}, 2000);
								
								document.getElementById("msgoverflow").style.display = "block";
							}
						}
						else {
							document.getElementById("content").innerHTML = "<br>Er zijn momenteel geen wijzigingen in het rooster voor de komende lesuren.<br>Volg het rooster in de Zermelo-app.";
							startCountdownZ(10);
						}
					}
					else if (response["type"] == "error") {
						console.error(response["message"]);
						document.getElementById("content").innerHTML = "<br>Roosterwijzigingen zijn momenteel niet beschikbaar.<br>Gebruik de Zermelo-app.<br><br><small><b>Details:</b> "+response["message"]+"</small>";
						startCountdownZ(10);
						// mocht hier nou telkens staan "token invalid" of iets wat daarop lijkt, open dan het bestand "cache.json"
						// en verander de volledige content van dit bestand in "{}" (zonder de aanhalingstekens). Vergeet niet op te slaan!
						// vaak lost dit het probleem op.
					}
					else if (response["type"] == "warning") {
						console.warn(response["message"]);
						document.getElementById("content").innerHTML = "<br>Roosterwijzigingen zijn momenteel niet beschikbaar.<br>Gebruik de Zermelo-app.<br><br><small><b>Details:</b> "+response["message"]+"</small>";
						startCountdownZ(10);
					}
					else {
						console.log("Unknown response type for Zermelo!\n" + response["message"]);
						document.getElementById("content").innerHTML = "<br>Roosterwijzigingen zijn momenteel niet beschikbaar.<br>Gebruik de Zermelo-app.<br><br><small><b>Details:</b> "+response["message"]+"</small>";
						startCountdownZ(10);
					}
				}
				catch(e) {
					console.warn("Invalid return type for Zermelo!\n" + xhrz.responseText);
					document.getElementById("content").innerHTML = "<br>Roosterwijzigingen zijn momenteel niet beschikbaar.<br>Gebruik de Zermelo-app.<br><br><small><b>Details:</b> ongeldig return type voor Zermelo request</small>";
					startCountdownZ(10);
				}
			};
			xhrz.send();
			</script>
		<?PHP } ?>
	<?PHP } else { ?>
		<script>
		// zoek naar nieuwe meldingen over 5 seconden
		setTimeout(function() {
			window.location.reload();
		}, 5000);
		</script>
		<script>
		document.onkeypress = function (e) {
			e = e || window.event;
			// use e.keyCode to find out which key has been pressed
			window.location.reload();
		};
		</script>
		<h1>Er zijn momenteel geen meldingen om weer te geven</h1>
		<p>Zodra er nieuwe meldingen beschikbaar zijn zullen deze hier verschijnen.</p>
	<?PHP } ?>
	<script>
	document.onkeypress = function (e) {
		e = e || window.event;
		// use e.keyCode to find out which key has been pressed
		if (e.keyCode == 13) {
			// [ ENTER ]
			window.location.href = "meldingen.php";
		}
		else if (e.keyCode == 102) {
			// [F]
			/*
			// gebruik volledig scherm
			var elem = document.getElementsByTagName("body")[0];
			if (elem.requestFullscreen) {
				elem.requestFullscreen();
			}
			else if (elem.mozRequestFullScreen) {
				elem.mozRequestFullScreen();
			}
			else if (elem.webkitRequestFullscreen) {
				elem.webkitRequestFullscreen();
			}
			else if (elem.msRequestFullscreen) 
				elem.msRequestFullscreen();
			}
			*/
			alert("Gebruik F11 om de meldingen in volledig scherm weer te geven.");
		}
		else {
			NProgress.done(true);
			
			if (e.keyCode == 32) {
				// [ SPACEBAR ]
				// ga niet automatisch door naar de volgende melding (of juist wel)
				if (window.location.href.indexOf("&no") > -1) {
					window.location.href = window.location.href.replace("&no", "");
				}
				else {
					window.location.href = window.location.href + "&no";
				}
			}
			else if (e.keyCode == 49) {
				// [1]
				// ga naar melding 1
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 0;
			}
			else if (e.keyCode == 50) {
				// [2]
				// ga naar melding 2
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 1;
			}
			else if (e.keyCode == 51) {
				// [3]
				// ga naar melding 3
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 2;
			}
			else if (e.keyCode == 52) {
				// [4]
				// ga naar melding 4
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 3;
			}
			else if (e.keyCode == 53) {
				// [5]
				// ga naar melding 5
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 4;
			}
			else if (e.keyCode == 54) {
				// [6]
				// ga naar melding 6
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 5;
			}
			else if (e.keyCode == 55) {
				// [7]
				// ga naar melding 7
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 6;
			}
			else if (e.keyCode == 56) {
				// [8]
				// ga naar melding 8
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 7;
			}
			else if (e.keyCode == 57) {
				// [9]
				// ga naar melding 9
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 8;
			}
			else if (e.keyCode == 48) {
				// [0]
				// ga naar melding 10
				window.location.href = window.location.href.split("&num")[0] + "&num=" + 9;
			}
			else {
				// ga naar de volgende melding
				window.location.href = window.location.href.split("&num")[0] + "&num=" + (num+1);
			}
		}
	};
	</script>
</body>
<body>
<?PHP } ?>