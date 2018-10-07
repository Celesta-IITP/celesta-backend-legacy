<?php
include 'dbConfig.php';
session_start();
function SQLInjFilter(&$unfilteredString){
		$unfilteredString = mb_convert_encoding($unfilteredString, 'UTF-8', 'UTF-8');
		$unfilteredString = htmlentities($unfilteredString, ENT_QUOTES, 'UTF-8');
		// return $unfilteredString;
}
$err = "";
$return = "";
$status = 0;
$ret = array();
$eveID = $_POST['event_id'];
$userID = $_POST['id'];
$eveName = $_POST['event_name'];
$unsubscribe = $_POST['unsub'];

if($userID==""){
	$err.= "Need to login";
	$status = 400;
}elseif($eveName==""){
	$err.= "Event name Invalid";
	$status = 400;
}elseif($eveID==""){
	$err.= "Event Invalid";
	$status = 400;
}

$hash = ((int)$eveID * 10000 ) + (int)$userID;

if($status!=400){
	SQLInjFilter($eveID);
	SQLInjFilter($userID);
	SQLInjFilter($eveName);

	if($unsubscribe==1){
		$sql = "DELETE FROM `eventreg` WHERE `eventreg`.`hash` = ". $hash;
	}else{
		$sql = "INSERT INTO `eventreg`(eveID,uID, eveName,hash) VALUES ('".$eveID."', '".$userID."', '".$eveName."', '".$hash."')";
	}
	if($link =mysqli_connect($servername, $username, $password, $dbname)){
		$result = mysqli_query($link,$sql);
		$ret['testing'] = "done";
    	if($result){
    		$status = 200;
    		if($unsubscribe==1){
    			$return = "Unregistered Successfully!\nYou are no longer registered for ". $eveName .".";
    		}else{
    			$return = "You have successfully registered for ". $eveName ."!";
    		}
    	}else{
    		if(mysqli_errno($link)==1062){
    			$status = 409;
    			$err.= "You are already registered to this event.";
    		}elseif($unsubscribe==1 &&  mysqli_affected_rows($link)) {
    			$status = 409;
    			$err.= "You were not registered to this event.";
    		}else{
    			$status = 400;
    			$err.= mysqli_errno($link).":".mysqli_error($link);
    		}
    	}
    }
}

if($status == 200){
	$ret["status"] = 200;
	$ret["event"] = $eveName;
	$ret["message"] = $return;
}else{
	$ret["status"] = $status;
	$ret["message"] = $err;
}

echo json_encode($ret);

?>


