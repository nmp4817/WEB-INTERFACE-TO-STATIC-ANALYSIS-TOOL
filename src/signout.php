<?php
	
	session_start();

	unset($_SESSION['user_id']);
	unset($_SESSION['access_level']);
	unset($_SESSION['email']);
	unset($_SESSION['displayName']);
    unset($_SESSION['access-token']);
    unset($_SESSION['refresh-token']);
    unset($_SESSION['id-token']);

    session_unset();
	session_destroy();

	header("Location: signin.php");

?>