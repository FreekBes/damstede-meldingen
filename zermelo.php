<?PHP
	error_reporting(0); ini_set('display_errors', 0);
	
	require_once("import/nogit.php");
	
	header('Content-Type: text/html; charset=utf-8');
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	
	// verkrijg alle beschikbare schermen
	$screens = json_decode(file_get_contents("screens.json"), true);
	
	$data = array();
	$data["type"] = "error";
	$data["message"] = "Unknown error";
	$data["data"] = array();
	
	function returnError($msg) {
		global $data;
		$data["type"] = "error";
		$data["message"] = $msg;
		$data["data"] = array();
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		die();
	}
	
	function returnWarning($msg) {
		global $data;
		$data["type"] = "warning";
		$data["message"] = $msg;
		$data["data"] = array();
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		die();
	}
	
	function returnData($msg, $stuff) {
		global $data;
		$data["type"] = "success";
		$data["message"] = $msg;
		$data["data"] = $stuff;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		die();
	}
	
	// Include de Zermelo API met de custom autoloader
	require_once("import/zermelo/custom_autoload.php");
	
	// Registreer (start) de Zermelo API
	register_zermelo_api();
	
	// Leeg de cache voor tokens.
	// Soms werkt dit niet, leeg cache.json dan handmatig door de volledige content te vervangen in "{}" (zonder aanhalingstekens)
	// $zermelo->getCache()->clearCache();
	$cacheCleared = file_put_contents("import/zermelo/cache.json", "{}");
	if ($cacheCleared === false) {
		returnWarning("Cache kon niet worden geleegd");
	}

	// Maak een nieuwe Zermelo instantie aan voor Damstede
	$zermelo = new ZermeloAPI($zermeloSchool);

	// Verkrijg een access token van Zermelo met behulp van een gebruikersnaam en wachtwoord
	$zermelo->grabAccessToken($zermeloUsername, $zermeloPassword);
	
	function isInvalid($a) {
		if (isset($_GET["allvalid"])) {
			return false;
		}
		$shortened = lessonsShortened(null, $a);
		return (
			empty($a["changeDescription"]) ||
			count($a["groups"]) < 1 ||
			count($a["teachers"]) < 1 ||
			count($a["subjects"]) < 1 ||
			count($a["locations"]) < 1 ||
			$a["end"] < time() - 600 ||
			(
				$a["cancelled"] === false &&
				$a["modified"] === false &&
				$a["moved"] === false &&
				!$shortened
			) ||
			(
				// geef geen 40-minutenrooster tijdswijzigingen weer
				$shortened &&
				strpos($a["changeDescription"], "Deze les is verplaatst (komt van ") == 0 &&
				strpos($a["changeDescription"], "lokaal is gewijzigd") === false
			)
		);
	}
	
	function filterOutNonChanged($json) {
		for($i = 0; $i < count($json); $i++) {
			if (isInvalid($json[$i])) {
				unset($json[$i]);
			}
		}
		$json = array_values($json);
		return $json;
	}
	
	function isNotFilteredGood($json) {
		for($i = 0; $i < count($json); $i++) {
			if (isInvalid($json[$i])) {
				return true;
			}
		}
		return false;
	}
	
	$shortenedLessons = false;
	function lessonsShortened($json, $lesson = null) {
		global $shortenedLessons;
		
		if (empty($lesson)) {
			if (empty($json)) {
				return false;
			}

			for ($i = 0; $i < count($json); $i++) {
				if (strpos($json[$i]["changeDescription"], "Deze les is verplaatst (komt van ") == 0) {
					$lesson = $json[$i];
					break;
				}
			}
			
			if (empty($lesson)) {
				$lesson = $json[0];
			}
		}
		$temp = ($lesson["end"] - $lesson["start"] < 2999);
		if ($temp && !$shortenedLessons) {
			$shortenedLessons = true;
		}
		return $temp;
	}
	
	$startTimesNormal = array('08:30', '09:20', '10:10', '11:20', '12:10', '13:30', '14:20', '15:10', '16:00');
	$lessonDurNormal = 50;
	$startTimesShortened = array('08:30', '09:10', '09:50', '10:50', '11:30', '12:10', '13:10', '13:50', '14:30');
	$lessonDurShortened = 40;
	
	function shortenAllUnshorted($json) {
		global $startTimesNormal, $lessonDurNormal, $startTimesShortened, $lessonDurShortened;
		
		for ($i = 0; $i < count($json); $i++) {
			$oldStartTime = $json[$i]["start"];
			$lesson = $json[$i]["startTimeSlot"] - 1;
			if ($oldStartTime == strtotime("today ".$startTimesNormal[$lesson])) {
				$newStartTime = strtotime("today ".$startTimesShortened[$lesson]);
			}
			else {
				$newStartTime = $oldStartTime;
			}
			
			$oldEndTime = $json[$i]["end"];
			$lessonBlocks = $json[$i]["endTimeSlot"] - $lesson;
			$oldLessonDur = $lessonDurNormal * $lessonBlocks * 60;		// in seconds
			if ($json[$i]["end"] - $json[$i]["start"] == $oldLessonDur) {
				$newLessonDur = $lessonDurShortened * $lessonBlocks * 60;	// in seconds
				$newEndTime = strtotime("today ".$startTimesShortened[$lesson]." +".$newLessonDur." second");
			}
			else {
				$newLessonDur = $oldLessonDur;
				$newEndTime = $oldEndTime;
			}
			
			$json[$i]["start"] = $newStartTime;
			$json[$i]["end"] = $newEndTime;
		}
		return $json;
	}
	
	function allowedByScreen($screenID) {
		global $screens;
		if (isset($screens[$screenID])) {
			$screen = $screens[$screenID];
			if ($screen["name"] != "Docentenkamer") {
				if ($screen["location"] == "Hoofdgebouw") {
					// in het hoofdgebouw hoeven enkel roosterwijzigingen voor de 3e t/m de 6e klas te worden weergegeven.
					$allow = array(3,4,5,6);
				}
				else {
					// op andere locaties hoeven enkel roosterwijzigingen voor de 1e en 2e klas te worden weergegeven.
					$allow = array(1,2);
				}
			}
			else {
				// in de docentenkamer moeten roosterwijzigingen voor de 1e t/m de 6e klas worden weergegeven.
				$allow = array(1,2,3,4,5,6);
			}
		}
		else {
			// als de locatie onbekend is dan worden alle roosterwijzigingen weergegeven (1 t/m 6)
			$allow = array(1,2,3,4,5,6);
		}
		return $allow;
	}
	
	function filterByScreen($json, $screenID) {
		$allowed = allowedByScreen($screenID);
		for($i = 0; $i < count($json); $i++) {
			$isAllowed = array();
			for ($j = 0; $j < count($json[$i]["groups"]); $j++) {
				$group = intval(preg_replace('/[^0-9]/', '', explode(".", $json[$i]["groups"][$j])[0]));
				array_push($isAllowed, in_array($group, $allowed));
			}
			if (!in_array(true, $isAllowed)) {
				unset($json[$i]);
			}
		}
		$json = array_values($json);
		return $json;
	}
	
	function isNotFilteredGoodByScreen($json, $screenID) {
		$allowed = allowedByScreen($screenID);
		for ($i = 0; $i < count($json); $i++) {
			$isAllowed = array();
			for ($j = 0; $j < count($json[$i]["groups"]); $j++) {
				$group = intval(preg_replace('/[^0-9]/', '', explode(".", $json[$i]["groups"][$j])[0]));
				array_push($isAllowed, in_array($group, $allowed));
			}
			if (!in_array(true, $isAllowed)) {
				return true;
			}
		}
		return false;
	}
	
	try {
		$date = "today";
		if (!isset($_GET["me"])) {
			// enkel roosterwijzigingen voor leerling 343443 zelf als GET me is gegeven in de URL
			$grid = $zermelo->getSchoolGrid('343443', strtotime($date), strtotime($date.=" + 1 day"));
		}
		else {
			// roosterwijzigingen voor alle leerlingen op Damstede
			$grid = $zermelo->getStudentGrid('343443', strtotime($date), strtotime($date.=" + 1 day"));
		}
		
		// $grid = $zermelo->resolveCancelledClasses($grid);
		
		// transformeer het rooster naar een JSON
		$json = json_decode($zermelo->formatJSON($grid), true);
		
		if (isset($_GET["screen"]) && !empty($_GET['screen'])) {
			// filter het rooster tot het goed is gefilterd voor dit specifieke scherm
			while (isNotFilteredGoodByScreen($json, $_GET["screen"])) {
				$json = filterByScreen($json, $_GET["screen"]);
			}
		}
		
		// controleer of er een verkort rooster gaande is
		lessonsShortened($json);
		
		// filter het rooster tot het goed is gefilterd
		while (isNotFilteredGood($json)) {
			$json = filterOutNonChanged($json);
		}
		
		if ($shortenedLessons) {
			$json = shortenAllUnshorted($json);
			
			$shortenedMessage = (object) array(
				'lastModified' => time(),
				'groups' => array('-'),
				'startTimeSlot' => 1,
				'endTimeSlot' => 9,
				'start' => strtotime("today ".$startTimesShortened[0]),
				'end' => strtotime("today ".end($startTimesShortened)." +".$lessonDurShortened." minute"),
				'subjects' => array('-'),
				'teachers' => array('-'),
				'locations' => array('-'),
				'changeDescription' => 'Let op: er geldt vandaag een '.$lessonDurShortened.'-minutenrooster!'
			);
			array_unshift($json, $shortenedMessage);
			returnData("Lesdata gevonden met een verkort rooster", $json);
		}
		else {
			returnData("Lesdata gevonden", $json);
		}
	}
	catch (Exception $e) {
		// mocht er iets fout gaan wordt met behulp van onderstaande code de error doorgegeven aan de opvrager (het meldingenscherm)
		returnError($e->getMessage());
	}
?>