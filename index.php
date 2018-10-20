<?php
 ini_set( "display_errors", 0); 
/**
* New request lands in this class.
* After that it is routed accordingly to the respective controller. 
*/
//require_once('servConf.php');
// echo "id=".$_SESSION['uID'];
class Routing
{
	function __construct()
	{
		return null;
	}
	public function Redirect($url)
	{
		return null;
	}
}
// echo "check";
$url = $_SERVER['REQUEST_URI'];
preg_match('@(.*)index.php(.*)$@', $_SERVER['PHP_SELF'], $mat );
$base = '@^'. $mat[1] ;
if(preg_match($base . 'cAPI/checkLogin?$@', $url, $match)){
	if(isset($_SESSION['uID'])){
			echo json_encode(array(1,$_SESSION['uID'],$_SESSION['uName'])) ;
		}else{
			echo json_encode(array(0)) ;
		}
}elseif (preg_match($base . '$@', $url, $match)) {
//	if(isset($_SESSION['uID'])){
//		require ('render/homeAgain.php');
//	} else{
		require ('render/index.html');
//	}
}
elseif (preg_match($base . 'login?$@', $url, $match)) {
	require ('render/loginController.php');
} elseif (preg_match($base . 'register?$@', $url, $match)) {
        require ('render/signUpController.php');
} elseif (preg_match($base . 'event_reg?$@', $url, $match)) {
        require ('render/eventRegistration.php');
} elseif (preg_match($base . 'resend?$@', $url, $match)) {
        require ('render/resend.php');
} elseif (preg_match($base . 'reset?$@', $url, $match)) {
        require ('render/reset.php');
} elseif (preg_match($base . 'forgot?$@', $url, $match)) {
        require ('render/forgot.php');
} elseif (preg_match($base . 'switchtoCA?$@', $url, $match)) {
        require ('render/switchtoCA.php');
} elseif (preg_match($base . 'cAPI/(.*)$@', $url, $match)) {
	require ('render/commonAPI.php');
} elseif (preg_match($base . 'event/([0-9]{2})/([0])|([0-9]{4})/(.*)$@', $url, $match)) {
	require ('render/eveReg.php');
} elseif (preg_match($base . 'ca_submit?$@', $url, $match)) {
        require ('render/ca_submit.php');
} else {
	http_response_code(404);
	require ('render/404.php');
	// die('invalid url ' . $url);
	die();
}
?>
