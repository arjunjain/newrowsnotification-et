<?php 
require_once '/webconfig/newrowsscreenname-config.php';
require_once 'PHPMailer/class.phpmailer.php';
require_once 'PHPMailer/class.smtp.php';
require_once 'PHPMailer/PHPMailerAutoload.php';

$base_path 				=	dirname(__FILE__);
$csv_filename 			=	$base_path."/ExportTweet-".date('Y-m-d-H-i-s').".csv";
$last_edit_file_name 	= 	$base_path."/last_edited_screenname.txt";

$last_edit_file 		= 	fopen($last_edit_file_name, "r");
$last_edited_time 		= 	'';
if(filesize($last_edit_file_name) > 0){
	$last_edited_time 	= 	fgets($last_edit_file);
}
fclose($last_edit_file);

$db_con = mysqli_connect($config['db_host'],$config['db_username'],$config['db_password'],$config['db_name']) or die("Some error occurred during connection " . mysqli_error($con));

$query = 'SELECT username FROM '.$config['db_table'];
if($last_edited_time != '')
	$query .= ' WHERE added_date >= "'.$last_edited_time.'"';
$query .= ' ORDER BY added_date desc';

$result = $db_con->query($query);

if (!$result) die('Couldn\'t fetch records');

$num_fields = mysqli_num_fields($result);

$headers = array();
for ($i = 0; $i < $num_fields; $i++) {
	$headers[] =mysqli_fetch_field_direct($result , $i)->name;
}

$fp = fopen($csv_filename, 'w');
if ($fp && $result) {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="'.$csv_filename.'"');
	header('Pragma: no-cache');
	header('Expires: 0');
	fputcsv($fp, $headers);
	while ($row = $result->fetch_array(MYSQLI_NUM)) {
		fputcsv($fp, array_values($row));
	}
}

$subject = "New Username | ".$config['app_name']." | ".date('Y-m-d');
$message = "New Username List";

$mail = new PHPMailer;
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = $config['mail_host']; 				 		  // Specify main and backup server
$mail->SMTPAuth = $config['mail_auth'];                               // Enable SMTP authentication
$mail->Username = $config['mail_user'];         // SMTP username
$mail->Password = $config['mail_password'];                     // SMTP password
$mail->SMTPSecure = $config['mail_secure'];                            // Enable encryption, 'ssl' also accepted
$mail->Port = $config['mail_port'];
$mail->Priority=1;
$mail->From = $config['mail_from'];//'no-reply@exporttweet.com';
$mail->FromName = $config['mail_from_name'];//ExportTweet';
$mail->addAddress($config['mail_to']);               // Name is optional
$mail->isHTML(true);
$mail->AddAttachment($csv_filename,basename($csv_filename));
$mail->Subject = $subject;
$mail->Body    = $message;
@$mail->send();
unlink($csv_filename);

$myfile = fopen($last_edit_file_name, "w");
$txt = date('Y-m-d H:i:s');
fwrite($myfile, $txt);
fclose($myfile);
?>