<?PHP
	error_reporting(1); ini_set('display_errors', 1);

	/*
	*	De code voor deze Zermelo API wrapper valt verder te vinden in
	*	./import/zermelo-api.php
	*/
	
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

	if (isset($_GET["allvalid"])) {
		// alleen voor debuggen!
		$appointments = $zermeloAPI->getAllAppointments();
	}
	else {
		$appointments = $zermeloAPI->getAppointments();
	}

	$appointmentCount = count($appointments);
	if ($appointmentCount > 0) {
		returnData($appointmentCount . " lesuren gevonden", $appointments);
	}
	else {
		returnData("0 lesuren gevonden", array());
	}
?>