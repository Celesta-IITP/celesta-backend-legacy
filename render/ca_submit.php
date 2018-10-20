<?php
session_start();
include 'dbConfig.php';
function SQLInjFilter(&$unfilteredString){
		$unfilteredString = mb_convert_encoding($unfilteredString, 'UTF-8', 'UTF-8');
		$unfilteredString = htmlentities($unfilteredString, ENT_QUOTES, 'UTF-8');
		// return $unfilteredString;
}
$error = "";
$return = "";
$sender = $_POST['sender'];
$clstID = 0;
$status = 0;
$ret = array();
//$json = file_get_contents('php://input');
//$obj = json_decode($json);
if (!isset($_POST['remarks'])) {
	$error .= "Invalid Remarks.";
	$status = 400;
}
if (!isset($_POST['points']) || $_POST['points']=="" || !is_numeric($_POST['points'])) {
	$error .= "Invalid points.";
	$status = 400;
}
if (!isset($_POST['id']) || !preg_match('/^[0-9]{4}$/',$_POST['id'])) {
	$error .= "Invalid ID ";
    $status = 400;
}if (!isset($_POST['sender']) || !preg_match('/^[0-9]{4}$/',$_POST['sender'])) {
	$error .= "Unauthorised user ";
    $status = 402;
}

//if (!isset($_POST['year']) || $_POST['year']<1 || $_POST['year']>4) {
//	$error .= "Year invalid. ";
//	$status = 400;
//}
if($status==0){
	//sql injection filter function call goes here
	SQLInjFilter($_POST['id']);
	SQLInjFilter($_POST['remarks']);
	SQLInjFilter($_POST['points']);

	//db stuff here
	$sql = "INSERT INTO `cascore`(`score`, `remarks`, `pID`) VALUES ('".$_POST['points']."', '".$_POST['remarks']."', '".$_POST['id']."')";
	//password field absent, otherwise also store sha1($_POST['password'])
	//assuming table name 'users' as not given in email
	$id=0;
	if($sender=="" && $_SESSION['uid']==$sender){
		if($link =mysqli_connect($servername, $username, $password, $dbname)){
			$result = mysqli_query($link,$sql);
		    if($result){
		    	$status=200;
		    	$return="Successfully Submitted";	 
	    	}else{
	    		//error to fetch result
	    		$status = 400;
	    		$error = "error submitting points ".mysqli_errno($link);
			//errorLog(mysqli_errno($link)." ".mysqli_error($link));
	    	}
		}else{
    		//error to connect to db
    		$status = 500;
    		$error = "error connecting to DB";
			$error.= "Debugging errno: " . mysqli_connect_errno();
		}
    }else{
    	$status = 402;
    	$error.= "Unauthorised User!";
    }
}
// $status=200;
// 	$return="Successfully Registered";
if($status == 200){
	$ret["status"] = 200;
	$ret["message"] = $return;
}else{
	$ret["status"] = $status;
	$ret["message"] = $error." For help, error reference no: ";
	
}

echo json_encode($ret);

?>
