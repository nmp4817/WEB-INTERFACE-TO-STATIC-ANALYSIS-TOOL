<?php


require_once 'inc/mail.local.php';

function email_confirm_emailaddress($emailToConfirm, $code ) {

	$host_baseurl = "http://localhost/~fbhombal/proto-optimus/src";
	$body = "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head>" .
			"<body><p>Your confirmation code is <strong>" . $code . "</strong></p><br>" . 
			"<p>Enter the code on the confirmation page. If the confirmation page is not showing, click <a href='" . host_baseurl() . "/confirmemail.php'>here</a></p>" .
			"<body>";

	$subject = "Re4i Confirmation Code: ". $code;

	$ret = SendRe4iMail($emailToConfirm, $subject, $body);

	return $ret;

}

?>
