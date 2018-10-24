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

$adminType = 0;

// URL parse
$qrParse = explode("/",$url);

// add all input type validations here.
function validate($type, $data){
    if ($type=='userID'){
        return (!preg_match('/[0-9]{4}/',$data))?false:true;
    }else if($type=='shaKey'){
        return (!preg_match('/[a-zA-Z0-9]{40}/',$data))?false:true;
    }else if($type=='qrHash'){
        if (sha1($pyHashSalt.".".ltrim(substr($data,-4),'0')) != substr($data,0,40))
            return false;
        return (!preg_match('/[a-zA-Z0-9]{40,45}/',$data))?false:true;
    }else if($type=='eventID'){ // event ID data validation here
        return (!preg_match('/[1-3][0-9]{4}/',$data))?false:true;
    }
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
    $sql = "SELECT pswd,isadmin FROM users WHERE `regID`= '".$_POST['uID']."'";
    if($link=mysqli_connect($servername, $username, $password, $dbname)){
        $result = mysqli_query($link,$sql);
        if($result || mysqli_num_rows($result)>0){
            $row = mysqli_fetch_array($result, MYSQL_ASSOC);
			if (sha1($row['pswd'].$qrHashSalt)==$_POST['val']){
			    $adminType = $row['isadmin'];
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
    http_response_code($statusCode);
    $res["message"] = $message;
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
    if (!validate('userID',$qrParse[4]) or !validate('qrHash',$qrParse[5])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    // auth middleware
    if (!auth() && $adminType==1){
        respond(403,"Authentication error.");
    }
    if ($day == 0)
        $day=1;
    
    $sql1 = "UPDATE users SET qrhash='".$qrParse[5]."',checkinday".$day."=COALESCE(checkinday".$day.",NOW()) WHERE `regID`= '".$qrParse[4]."'";
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
    
    
}else if($qrParse[3]=='getEvents'){ // qr/getEvents/
    //get list of events
    $sql1 = "SELECT id,name FROM `events`";
    if($link=mysqli_connect($servername, $username, $password, $dbname)){
        $result1 = mysqli_query($link,$sql1);
        if($result1 || mysqli_num_rows($result1)>0){
            $list_events = array();
            if ($adminType == 1 || $adminType == 2 ){
                array_push($list_events, ["id"=>"0","name"=>"Registration: PAIR QR"]);
                array_push($list_events, ["id"=>"1","name"=>"Security: CHECK-IN"]);    
                array_push($list_events, ["id"=>"2","name"=>"Security: CHECK-OUT"]);
            }
            else if($adminType == 3){
                array_push($list_events, ["id"=>"1","name"=>"Security: CHECK-IN"]);    
                array_push($list_events, ["id"=>"2","name"=>"Security: CHECK-OUT"]);
            }else if($adminType == 1 || $adminType == 4){
                while($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)){
                    array_push($list_events, $row1);
                }    
            }
            respond(200,"List of events fetched", $list_events);
        }else{
            respond(401,"Events not found.");
        }
    }else{
        respond(500,"DB error".mysql_error($link));
    }
    
}else if($qrParse[3]=='getReg'){ // qr/getReg/{eventID}/
    //get list of registered users for event {eventID}
    // return data with function call to respond($statusCode, $message, $object)
    if (!isset($qrParse[4])){
        respond(400,"Insuffecient parameters for 'getReg'.");
    }
    if (!validate('eventID',$qrParse[4])){
        respond(400,"Insuffecient parameters for 'getReg'.");
    }
    // auth middleware
    if (!auth()){
        respond(403,"Authentication error.");
    }
    $sql1 = "SELECT regID,name,email,phone,college FROM `users` WHERE regID IN (SELECT uID from eventreg WHERE eveID=". $qrParse[4] ." AND participated=1)";
    if($link=mysqli_connect($servername, $username, $password, $dbname)){
        $result1 = mysqli_query($link,$sql1);
        if($result1 || mysqli_num_rows($result1)>0){
            $list_users = array();
            while($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)){
                array_push($list_users, $row1);
            }
            respond(200,"List of users fetched", $list_users);
        }else{
            respond(401,"Users not found.");
        }
    }else{
        respond(500,"DB error".mysql_error($link));
    }
    
}else if($qrParse[3]=='setReg'){ // qr/setReg/{eventID}/{qrHash}
    //register user for event
    // return data with function call to respond($statusCode, $message, $object)
    if (!isset($qrParse[4]) or !isset($qrParse[5])){
        respond(400,"Insuffecient parameters for 'setReg'.");
    }
    if (!validate('eventID',$qrParse[4]) or !validate('qrHash',$qrParse[5])){
        respond(400,"Insuffecient parameters for 'setReg'.");
    }
    // auth middleware
    if (!auth()){
        respond(403,"Authentication error.");
    }
    //if ($checkedin) {
        $sql1 = "SELECT regID FROM `users` WHERE qrhash='".$qrParse[5]."'";
        if($link=mysqli_connect($servername, $username, $password, $dbname)){
            $result1 = mysqli_query($link,$sql1);
            if($result1 || mysqli_num_rows($result1)>0){
                while($row1 = mysqli_fetch_array($result1, MYSQL_ASSOC)){
                    $hash = ((int)$qrParse[4] * 10000 ) + (int)$row1['regID'];
                    $sql3 = "INSERT INTO `eventreg`(eveID,uID, eveName,hash, participated) VALUES ('".$qrParse[4]."', '".$row1['regID']."', (SELECT name from events where id = ".$qrParse[4]."), '".$hash."', 1) ON DUPLICATE KEY UPDATE participated=VALUES(participated)";
                    $result3 = mysqli_query($link,$sql3);
                    if($result3){
                        respond(200,"user registered!");
                    }else{
                        respond(401,"User not found.");
                    }  
                }
            }else{
                respond(401,"User not found.");
            }
        }else{
            respond(500,"DB error".mysql_error($link));
        }
    //}else{
    //    respond(402,"User has not checked in.");
    //}
    
}else if($qrParse[3]=='checkin'){ // qr/checkin/{qrHash}
    //mark user table indicating person is in campus
    
    if (!isset($qrParse[4])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    if (!validate('qrHash',$qrParse[4])){
        respond(400,"Insuffecient parameters for 'pair'.");
    }
    
    // auth middleware
    if (!auth()){
        respond(403,"Authentication error.");
    }

    if ($day == 0)
        $day = 1;
    
    $sql1 = "UPDATE users SET checkinday".$day." = now(),isext=1 WHERE `qrhash`= '".$qrParse[4]."'";
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
    if (!validate('qrHash',$qrParse[4])){
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
