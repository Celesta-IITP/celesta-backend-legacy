<?php
include 'dbConfig.php';
include 'render/log.php';
include 'render/checkAccess.php';
function SQLInjFilter(&$unfilteredString){
	$unfilteredString = mb_convert_encoding($unfilteredString, 'UTF-8', 'UTF-8');
	$unfilteredString = htmlentities($unfilteredString, ENT_QUOTES, 'UTF-8');
	// return $unfilteredString;
}
$error = "";
$return = "";
$status = 0;
$name = array();
$ID = array();
$points = array();
$ret = array();

	//$debug = "in here1";
	//SQL inj sanitation here?
	SQLInjFilter($_POST['emailid']);
	SQLInjFilter($_POST['password']);
	//db stuff here
	$sql = "SELECT name,CA.caID AS ID,(SELECT count(*) FROM users P2 WHERE P2.caID = CA.caID AND P2.isCA<>0)*20 + (SELECT COALESCE(SUM(score),0) AS score FROM cascore P3 WHERE P3.pID = CA.caID) + 20 AS Score FROM users CA WHERE CA.isCA<>0 AND CA.name NOT LIKE 'test%' ORDER BY Score DESC,name LIMIT 15";
	if($link =mysqli_connect($servername, $username, $password, $dbname)){
		$result = mysqli_query($link,$sql);
	    if(!$result || mysqli_num_rows($result)<1){
	    	$status=400;
	    	$error="Error fetching result.";
			errorLog(mysqli_errno($link)." ".mysqli_error($link));
	    }else {
	    	$status=200;
	    	$return = "Success.";
	    	while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
	    	    array_push($name,$row['name']);
				array_push($ID,$row['ID']);
				array_push($points,$row['Score']);
	    	}
	    }
    }else{
    	//error to connect to db
    	$status = 500;$debug.="in6:";
    	$error = "error connecting to DB";
		errorLog(mysqli_errno($link)." ".mysqli_error($link));
    }
}
if($status == 200){
	$ret["name"] = $name;
	$ret["ID"] = $ID;
	$ret["points"] = $points;
	$ret["status"] = $status; 
	$ret["message"] = $return;
}else{
	$ret["status"] = $status;
	$ret["message"] = $error." For help, error reference no: $errRef ";//.$_POST['emailid'].'  -  '.$_POST['password'];
	errorLog($error);
}
echo json_encode($ret);
?>
