<?php
include 'dbConfig.php';
require('defines.php');
require('emailCredential.php');
require_once('resources/PHPMailer/PHPMailerAutoload.php');
include 'render/checkAccess.php';
include 'render/log.php';
function SQLInjFilter(&$unfilteredString){
		$unfilteredString = mb_convert_encoding($unfilteredString, 'UTF-8', 'UTF-8');
		$unfilteredString = htmlentities($unfilteredString, ENT_QUOTES, 'UTF-8');
		// return $unfilteredString;
}
$error = "";
$return = "";
$id = 0;
$status = 0;
$ret = array();
//$json = file_get_contents('php://input');
//$obj = json_decode($json);
if (!isset($_POST['id']) || $_POST['id']=="") {
	$error .= "ID invalid. ";
	$status = 400;
}

if($status!=400){
	//sql injection filter function call goes here
	SQLInjFilter($_POST['id']);
	$id = $_POST['id'];
	//db stuff here
	$sql = "SELECT * FROM users WHERE regID=". $id;
    //password field absent, otherwise also store sha1($_POST['password'])
	//assuming table name 'users' as not given in email
	if($link =mysqli_connect($servername, $username, $password, $dbname)){
	    $result = mysqli_query($link,$sql);
	    if($result || mysqli_num_rows($result)>0){
            while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
			    $id=$row['regID'];
	            $mail = new PHPMailer;
            // 0 = off (for production use)
            // 1 = client messages
            // 2 = client and server messages
            // 3 = verbose debug output
            /*$mail->SMTPDebug = 0;
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
            $mail->Subject = "Celesta 2018 registration confirmation";
            $mail->Body    = "Registered!\nHi ".$_POST['name'].",\n Thank you for registering for Celesta'18. Your Registered Id is : CLST$id .\n";
            $altBody = "Hi name,\nThank you for registering for Celesta'18. Your Registered Id is : CLST$id .\n";   
            $mail->AltBody = $altBody;
            $mail->send();
            //send SMS*/
                $idstr = (string)sprintf("%04d", $id);
                $otp = ($id * 7)%10000;
                if ($otp<1000)
                    $otp = 1000 + $otp;
                $message = "Registered!<br>Hi ".$row['name'].",<br> Thank you for registering for Celesta'18. Your Registered Id is : CLST$idstr .<br>Login to <a href='http://celesta.org.in'>celesta.org.in</a> for updates.<br>Please confirm your account by entering the OTP sent to your mobile <a href='https://celesta.org.in/apiLe/confirm/$id/". sha1($otp+18) ."/'>here</a><br><br>Use <a href='https://www.thecollegefever.com/events/celesta-2018-yEYo3DFhwl'>this portal</a> for online payments.<br><!---Download android app from playstore Download app: <a href='https://goo.gl/YxPbFG'>https://goo.gl/YxPbFG</a> --><br>Web Sponsor: <a href='http://asaphosting.in'>asaphosting.in</a>";
                mailTo($row['email'],"Celesta 2018 registration confirmation",$message);
                //send SMS
                
                $status = 200;
                $return = "Success resent";
            }
	    } else {
	    	//error to fetch result
	    	$status = 400;
	    	$error = "error to fetch result ".mysqli_errno($link);
		errorLog(mysqli_errno($link)." ".mysqli_error($link));
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
	$ret["id"] = $id;
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
