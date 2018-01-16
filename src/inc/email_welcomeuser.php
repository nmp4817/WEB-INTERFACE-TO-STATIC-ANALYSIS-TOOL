<?php

// TEST file for Email. Include the function directly and

//require_once 'Mail.php';

// require 'inc/mail.local.php';

require_once 'inc/mail.local.php';

function email_welcomeuser($emailToConfirm, $displayName ) {

	$body = "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head>" .
			"<body><p>Dear " . $displayName . 
			"<br><br>Welcome to the Re4i marketplace.</p>" .
			"<body>";

	$subject = "Welcome to Re4i";

	$ret = SendRe4iMail($emailToConfirm, $subject, $body);

	return $ret;
}


?>
