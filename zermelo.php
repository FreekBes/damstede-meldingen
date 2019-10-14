<?PHP
	error_reporting(1); ini_set('display_errors', 1);
	
	require_once("import/nogit.php");
	require_once("import/zermelo-api.php");
	$zermeloAPI = new Zermelo();
	$zermeloAPI->setSchool($zermeloSchool);
	$zermeloAPI->setApiToken($zermeloApiToken);
	$zermeloAPI->setBranchOfSchool($zermeloBranchOfSchool);
	
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

	$appointments = $zermeloAPI->getAppointments();
	$appointments = filterByScreen($appointments, $_GET["screen"]);
	$appointmentCount = count($appointments);
	if ($appointmentCount > 0) {
		returnData($appointmentCount . " lesuren gevonden", $appointments);
	}
	else {
		returnData("0 lesuren gevonden", array());
	}
?>