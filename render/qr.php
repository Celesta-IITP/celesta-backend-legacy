<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

include 'dbConfig.php';
require('defines.php');

header('Content-Type: application/json');


$res = ["status" => 400,"message"=>"Invalid parameters"];

// date handling
$today = new DateTime("today");
if ($today==$day1)
    $day = 1;
else if ($today==$day2)
    $day = 2;
else
    $day = 0;

$checkedin = false;

// URL parse
$qrParse = explode("/",$url);

// add all input type validations here.
function validate($type, $data){
    if ($type=='userID'){
        return (!preg_match('/[0-9]{4}/',$data))?false:true;
    }else if($type=='shaKey'){
        return (!preg_match('/[a-zA-Z0-9]{40}/',$data))?false:true;
    }/*else if($type=='eventID'){ // event ID data validation here
        return (!preg_match('/[a-zA-Z0-9]{40}/',$data))?false:true;
    }*/
}

// call this function as middleware for all APIs that require authentication.
function auth(){
    global $servername, $username, $password, $dbname, $qrHashSalt,$checkedin,$day;
    
    if (!isset($_POST['uID']) or !isset($_POST['val'])){
        return false;
    }
    if (!validate('userID',$_POST['uID']) or !validate('shaKey',$_POST['val'])){
        return false;
    }
    // proceed if all inputs are in valid format.
    $sql = "SELECT pswd,checkinday1,checkinday2 FROM users WHERE `regID`= '".$_POST['uID']."'";
    if($link=mysqli_connect($servername, $username, $password, $dbname)){
        $result = mysqli_query($link,$sql);
        if($result || mysqli_num_rows($result)>0){
            $row = mysqli_fetch_array($result, MYSQL_ASSOC);
			if (sha1($row['pswd'].$qrHashSalt)==$_POST['val']){
			    if(isset($row['checkinday'.$day]))
			        $checkedin = true;
			    return true;
		    }
			    
        }
    }else{
        respond(500,"DB error".mysql_error($link));
    }
    return false;
}

// creates response and outputs as JSON. 
function respond($statusCode, $message, $object = []){
    $res["status"] = $statusCode;
    http_response_code(403);
    $res["message"] = $message;
    if ($object)
        $res["data"] = $object;
    echo json_encode($res);
    exit(1);
}

// API endpoint for pairing celestaID with a QR hash
// @todo: set a valid set of QRs
if ($qrParse[3]=='pair'){ // qr/pair/{clstID}/{qrHash}
    if (!isset($qrParse[4]) or !isset($qrParse[5])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    if (!validate('userID',$qrParse[4]) or !validate('shaKey',$qrParse[5])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    // auth middleware
    if (!auth()){
        respond(403,"Authentication error.");
    }
    if ($day == 0)
        $day=1;
    if ($checkedin)
        $sql1 = "UPDATE users SET qrhash='".$qrParse[5]."' WHERE `regID`= '".$qrParse[4]."'";
    else
        $sql1 = "UPDATE users SET qrhash='".$qrParse[5]."',checkinday".$day."=now() WHERE `regID`= '".$qrParse[4]."'";
    if($link=mysqli_connect($servername, $username, $password, $dbname)){
        $result1 = mysqli_query($link,$sql1);
        if($result1 == 1){
            respond(200,"Paired!");
        }else{
            respond(401,"User not found.");
        }
    }else{
        respond(500,"DB error".mysql_error($link));
    }
    
    
}else if($qrParse[3]=='getReg'){ // qr/event/{eventID}/
    //get list of registered users for event {eventID}
    // return data with function call to respond($statusCode, $message, $object)
    
}else if($qrParse[3]=='setReg'){ // qr/event/{eventID}/{qrHash}
    //register user for event
    // return data with function call to respond($statusCode, $message, $object)
    
}else if($qrParse[3]=='checkin'){ // qr/checkin/{qrHash}
    //mark user table indicating person is in campus
    
    if (!isset($qrParse[4])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    if (!validate('shaKey',$qrParse[4])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    
    // auth middleware
    if (!auth()){
        respond(403,"Authentication error.");
    }

    if ($day == 0)
        $day = 1;

    $sql1 = "UPDATE users SET checkinday".$day." = now() WHERE `qrhash`= '".$qrParse[4]."'";
    if($link=mysqli_connect($servername, $username, $password, $dbname)){
        $result1 = mysqli_query($link,$sql1);
        if($result1 == 1){
            respond(200,"Checked-In!");
        }else{
            respond(401,"User not found.");
        }
    }else{
        respond(500,"DB error".mysql_error($link));
    }
    
    
}else if($qrParse[3]=='checkout'){ // qr/checkout/{userID}
    //mark user table indicating person has left campus
    
    if (!isset($qrParse[4])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    if (!validate('shaKey',$qrParse[4])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    
    // auth middleware
    if (!auth()){
        respond(403,"Authentication error.");
    }

    if ($day == 0)
        $day = 2;

    $sql1 = "UPDATE users SET checkoutday".$day." = now() WHERE `qrhash`= '".$qrParse[4]."'";
    if($link=mysqli_connect($servername, $username, $password, $dbname)){
        $result1 = mysqli_query($link,$sql1);
        if($result1 == 1){
            respond(200,"Checked-Out!");
        }else{
            respond(401,"User not found.");
        }
    }else{
        respond(500,"DB error".mysql_error($link));
    }
}

echo json_encode($res);

?>
