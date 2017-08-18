<?php
 ini_set( "display_errors", 1); 
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
} elseif (preg_match($base . 'cAPI/(.*)$@', $url, $match)) {
	require ('render/commonAPI.php');
} else {
	http_response_code(404);
	require ('render/404.php');
	// die('invalid url ' . $url);
	die();
}
?>