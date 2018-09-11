<?php
include 'dbConfig.php';
require('resources/PHPMailer/PHPMailerAutoload.php');
require('defines.php');
require('emailCredential.php');
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

if (!isset($_POST['emailid']) || !filter_var($_POST['emailid'], FILTER_VALIDATE_EMAIL) ) {
	$error .= "EmailID invalid. ";
	$status = 400;
}

if($status!=400){
	//$debug = "in here1";
	//SQL inj sanitation here?
	SQLInjFilter($_POST['emailid']);
	//db stuff here
	$sql = "SELECT * FROM users WHERE email='".$_POST['emailid']."'";
	if($link =mysqli_connect($servername, $username, $password, $dbname)){
		$result = mysqli_query($link,$sql);
	    if(!$result || mysqli_num_rows($result)<1){
	    	$status=400;
	    	$error="Error fetching result.";
			errorLog(mysqli_errno($link)." ".mysqli_error($link));
	    }else {
	    	while($row = mysqli_fetch_assoc($result)){
	    		$status=200;
	    		$return = "Success.";
	    		$id = $row['regID'];
        		$key = $row['regID'] * 12;
        		$key = 147+$key;
				$message = "Hi ".$row['name'].",<br>Your Registered Id is : CLST".$id." .<br>To reset your celesta'18 Login password click <a href='https://celesta.org.in/reset/reset.php?".$id.sha1($key)."'>here</a><br>Web Sponsor: <a href='http://asaphosting.in'>asaphosting.in</a>";
    	    	mailTo($_POST['emailid'],"Celesta 2018 Reset Login Passcode",$message);
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
