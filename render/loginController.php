
<?php
session_start();
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
$uID=-1;
$ret = array();
if (!isset($_POST['emailid'])) {
	$error .= "Celesta ID blank. ";
	$status = 400;
}
if (!isset($_POST['password']) || $_POST['password']=='' ) {
        $error .= "password blank. ";
        $status = 400;
}

if($status!=400){
	//$debug = "in here1";
	//SQL inj sanitation here?
	SQLInjFilter($_POST['emailid']);
	SQLInjFilter($_POST['password']);
	$celesta_id = substr($_POST['emailid'], 4);
	//db stuff here
	$sql = "SELECT * FROM users WHERE `regID`= '".$celesta_id."'";
	if($link =mysqli_connect($servername, $username, $password, $dbname)){
	$result = mysqli_query($link,$sql);
	    if(!$result || mysqli_num_rows($result)<1){
	    	$status=403;// $debug .=mysqli_error($link)."  in2:    ". mysqli_num_rows($result);
	    	$return="Invalid credentials. Access Forbidden.";
		errorLog(mysqli_errno($link)." ".mysqli_error($link));
	    } else {$debug.="  in3 ".mysqli_num_rows($result);
	    	while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
	    		if($row['pswd']==sha1($_POST['password'])){
//$debug.="in4:".$row['pswd']." hmm: ".sha1($_POST['password']);
	    			$status=200;
	    			$return="Welcome ".$row['name'];
				$uName=$row['name'];
				$college=$row['college'];
				$uID=$row['regID'];
				$_SESSION['uid']=$uID;
				$_SESSION['name'] = $row['name'];
	    			//set sessionID etc etc...
	    		}else{//$debug.="in5:".$row['pswd']." hmm: ".sha1($_POST['password']);
	    			$status=403;
	    			$return="Invalid credentials. Access Forbidden.";	
				errorLog(mysqli_errno($link)." ".mysqli_error($link));
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
	$ret["userID"] = $uID;
	$ret["name"]=$uName;
	$ret["college"]=$college;
	$ret["events"]=['mayank','chutiya'];
	$ret["events"]=['Piyush','bhi','chutiya'];
	$ret["message"] = $return;

}else{
	$ret["status"] = $status;
	$ret["message"] = $error." For help, error reference no: $errRef ";//.$_POST['emailid'].'  -  '.$_POST['password'];
	errorLog($error);
}
echo json_encode($ret);

?>
