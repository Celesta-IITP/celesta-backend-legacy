<?php
include 'dbConfig.php';
require('defines.php');
include 'render/checkAccess.php';
include 'render/log.php';
function SQLInjFilter(&$unfilteredString){
	$unfilteredString = mb_convert_encoding($unfilteredString, 'UTF-8', 'UTF-8');
	$unfilteredString = htmlentities($unfilteredString, ENT_QUOTES, 'UTF-8');
	// return $unfilteredString;
}
$error = "";
$return = "";
$status = 0;
$ret = array();

if (!isset($_POST['id'])) {
	$error .= "ID invalid. ";
	$status = 400;
}
if (!isset($_POST['password'])) {
	$error .= "Password invalid. ";
	$status = 400;
}
if($status!=400){
	//$debug = "in here1";
	//SQL inj sanitation here?
	SQLInjFilter($_POST['id']);
	SQLInjFilter($_POST['password']);
	//db stuff here
	$sql = "SELECT * FROM users WHERE regID='".$_POST['id']."'";
	if($link=mysqli_connect($servername, $username, $password, $dbname)){
		$result = mysqli_query($link,$sql);
	    if(!$result || mysqli_num_rows($result)<1){
	    	$status=400;
	    	$error="Error fetching result.";
			errorLog(mysqli_errno($link)." ".mysqli_error($link));
	    }else {
	    $debug.="  in3 ".mysqli_num_rows($result);
        if($row = mysqli_fetch_assoc($result)) {
            $sql = "UPDATE users SET pswd = '".sha1($_POST['password'])."' WHERE regID = '".$row['regID']."'";
            $result = mysqli_query($link,$sql);
            if(!$result){ 
                $status = 402;
                $error="Cannot change password";
            } else {
                $status=200;
                $return="Password change successsful";
            }
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
	$ret["status"] = 200; 
	$ret["message"] = $return;
}else{
	$ret["status"] = $status;
	$ret["message"] = $error." For help, error reference no: $errRef ";//.$_POST['emailid'].'  -  '.$_POST['password'];
	errorLog($error);
}
echo json_encode($ret);
?>
