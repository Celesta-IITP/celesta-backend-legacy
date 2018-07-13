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
$caID = 0;
$isCA = 0;
$clstID = 0;
$status = 0;
$ret = array();
//$json = file_get_contents('php://input');
//$obj = json_decode($json);
if (!isset($_POST['name']) || $_POST['name']=="") {
	$error .= "Name invalid. ";
	$status = 400;
}
if (!isset($_POST['emailid']) || !filter_var($_POST['emailid'], FILTER_VALIDATE_EMAIL) ) {
	$error .= "emailID invalid. ";
	$status = 400;
}
if (!isset($_POST['password']) || $_POST['password']=='' ) {
        $error .= "password empty ";
        $status = 400;
}
if (!isset($_POST['mobile']) || !preg_match('/^[789][0-9]{9}$/',$_POST['mobile'])) {//simple regex for mobile validation
	$error .= "Mobile no Invalid. ";
	$status = 400;
}
if (!isset($_POST['college']) || $_POST['college']=="") {
	$error .= "College blank. ";
	$status = 400;
}
if (isset($_POST['caID']) && $_POST['college']!="") {
	$caID = $_POST['caID'];
}
if (isset($_POST['isCA']) && $_POST['college']!="") {
	$isCA = $_POST['isCA'];
}

//if (!isset($_POST['year']) || $_POST['year']<1 || $_POST['year']>4) {
//	$error .= "Year invalid. ";
//	$status = 400;
//}
if($status!=400){
	//sql injection filter function call goes here
	SQLInjFilter($_POST['mobile']);
	SQLInjFilter($_POST['emailid']);
	SQLInjFilter($_POST['college']);
	SQLInjFilter($_POST['password']);
	SQLInjFilter($_POST['name']);
	SQLInjFilter($_POST['caID']);
	SQLInjFilter($_POST['isCA']);
	//db stuff here
	$sql = "INSERT INTO `users`(name,email, phone,pswd, college, year, caID, isCA) VALUES ('".$_POST['name']."', '".$_POST['emailid']."', '".$_POST['mobile']."', '".sha1($_POST['password'])."', '".$_POST['college']."','1', '". $caID ."', '". $isCA ."')";
	//password field absent, otherwise also store sha1($_POST['password'])
	//assuming table name 'users' as not given in email
	$id=0;
	if($link =mysqli_connect($servername, $username, $password, $dbname)){
	$result = mysqli_query($link,$sql);
	    if($result){
	    	$status=200;
	    	$return="Successfully Registered";
		 $sql = "SELECT * FROM users WHERE `email`= '".$_POST['emailid']."'";
        if($link =mysqli_connect($servername, $username, $password, $dbname)){
        $result = mysqli_query($link,$sql); $id=0;
            if($result || mysqli_num_rows($result)>0){
                while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
			$id=$row['regID'];
		}
	    }}	
	
	$clstID =$id;
	$mail = new PHPMailer;
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        // 3 = verbose debug output
        $mail->SMTPDebug = 0;
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = MAIL_HOST;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = MAIL_SMTP_AUTH;                               // Enable SMTP authentication
        $mail->Username = MAIL_USERNAME;                 // SMTP username
        $mail->Password = MAIL_PASSWORD;                           // SMTP password
        $mail->SMTPSecure = MAIL_SMTP_SECURE;                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = MAIL_PORT;                                    // TCP port to connect to
        $mail->setFrom($ANWESHA_REG_EMAIL, 'Celesta Web and App Team');
        $mail->addAddress($_POST['emailid'], $_POST['name']);     // Add a recipient
        // $mail->addAddress('ellen@example.com');               // Name is optional
        $mail->addReplyTo($ANWESHA_REG_EMAIL, 'Registration & Planning Team');
        // $mail->addCC('guptaaditya.13@gmail.com');
        // $mail->addBCC($ANWESHA_YEAR);
        // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = "Celesta 2017 registration confirmation";
        $mail->Body    = "Registered!\nHi ".$_POST['name'].",\n Thank you for registering for Celesta'17. Your Registered Id is : TAM$id .\n";
        $altBody = "Hi name,\nThank you for registering for Celesta'17. Your Registered Id is : TAM$id .\n";
        $mail->AltBody = $altBody;
        $mail->send();
        //send SMS
        $contents="Thank you for registering for Celesta.\nYour Celesta ID is CEL$id\nWeb Sponsor asaphosting.in";
        $xml = file_get_contents("http://loginsms.in/api/mt/SendSMS?APIKey=".$APIKey."&senderid=CELSTA&channel=Trans&DCS=0&flashsms=0&number=".$_POST['mobile']."&text=".$contents);

	    } else {
	    	//error to fetch result
	    	$status = 400;
	    	$error = "error to fetch result ".mysqli_errno($link);
		errorLog(mysqli_errno($link)." ".mysqli_error($link));
	    }
	if(mysqli_errno($link)==1062){
		$status = 409;
		if(strpos(mysqli_error($link), 'email') !== false)
			$error = "Duplicate entry for email ID.";
		else if(strpos(mysqli_error($link), 'phone') !== false)
			$error = "Duplicate entry for phone number.";
		else
			$error = "Duplicate entry";
	}
    }else{
    	//error to connect to db
    	$status = 500;
    	$error = "error connecting to DB";
	$error.=   "Debugging errno: " . mysqli_connect_errno();
	errorLog(mysqli_errno($link)." ".mysqli_error($link));
    }
}
// $status=200;
// 	$return="Successfully Registered";
if($status == 200){
	$ret["status"] = 200;
	$ret["id"] = $clstID;
	$ret["message"] = $return;
}else{
	$ret["status"] = $status;
	$ret["message"] = $error ." For help, error reference no: $errRef";
	errorLog($error);
}
//$ret['deb']=$_POST['deb'];
//$data_back = json_decode(file_get_contents('php://input'));
//echo $data_back->{"data1"};
//echo var_dump($obj);
//http_response_code($status);
echo json_encode($ret);

?>
