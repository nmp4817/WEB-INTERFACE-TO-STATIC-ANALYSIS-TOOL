<?php
    /* ------------------------------ TODO: Use mysqli->real_escape_string to get protection against sql injection ------------------------------------ */
    require_once 'inc/mysql1.php';

    session_start();

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // TODO: What if someone post from outside our domain
    // Need to check if posted from the same page  $_SERVER host referer

    // Check and forward to 500
    
    //date_default_timezone_set('America/Chicago');

    if ( isset($_POST['validate']) ) {

        //validate button is clicked

        // TODO: Check SQL injection stuff

        //array for error and success messages
        $post_errors = Array();
        $post_success = Array();

    	try{    
	        //post variables
	        $col_confirmationCode = $_POST['confirmation-code'];

	        if ( isset($_POST['email']) ) {
	            //email input is not disabled and posted
	            $col_email = $_POST['email'];
	        } else {
	            //email is disabled and posted from hidden-email
	            $col_email = $_POST['hidden-email'];
	        }

	        /* ------Front-End Errors------- */

	        //email address is in invalid format
	        if ( !filter_var($col_email, FILTER_VALIDATE_EMAIL) ) { 
	            $post_errors[] = "Invalid email address";
	            $error_email = true;
	        }

	        /* ------End Front-End Errors------ */


	        if ( sizeof($post_errors) == 0 ) {
	            
	            //No Front-End Errors

	            /* ------ update accessLevel and memberSince if confirmation code is correct for email and fetch displayName and send welcome email to user ------ */

	            //update accessLevel and memberSince if confirmation code is correct for email
	            if ( $update_stmt = $mysqli->prepare("UPDATE users SET accessLevel = 100, memberSince = CURRENT_TIMESTAMP WHERE email=? AND code=?") ) {
	                
	                $update_stmt->bind_param('si', $col_email, $col_confirmationCode);
	                $update_status = $update_stmt->execute();
	                if ( $update_status === FALSE ) {
	                    $update_stmt->close();
	                    header("Location: system-error.php");
	                    //error occured
	                    // error_log(">>> EXECUTE Error: " . $update_stmt->error);
	                } elseif( $mysqli->affected_rows == 0 ) {
	                    
	                    //incorrect confirmation code entered
	                    $update_stmt->close();
	                    $post_errors[] = "Validation code is incorrect!";
	                    $error_validationCode = true;
	                } else {
	                    //record updated successfully
	                	$update_stmt->close();
	                    //fetch displayName and send welcome email
	                    if ( $select_stmt = $mysqli->prepare("SELECT displayName from users WHERE email = ?") ) {

	                        $select_stmt->bind_param("s", $col_email);
	                        $select_stmt->execute();
	                        $select_stmt->bind_result($displayName);
	                        $select_stmt->fetch();

	                        if ( $displayName == NULL ) {
	                        	$select_stmt->close();
	                            //error occured
	                            // error_log(">>> EXECUTE Error: " . $select_stmt->error);
	                        } else {
	                            $select_stmt->close();
	                            //sending welcome email
	                            require_once 'inc/email_welcomeuser.php';
	                            $ret = email_welcomeuser($col_email,$displayName);
	                            // TODO: check return value and pupup box if there was an error.
	                            header("Location: signin.php"); 
	                        }
	                    }
	                }
	            }
	        }
	    } catch ( mysqli_sql_exception $me ) {
            header("Location: system-error.php");
        } catch ( Exception $e ) {
            header("Location: system-error.php");
        }
    } elseif ( isset($_POST['resend'])) {

        //resend button is clicked

        //array for error and success messages
        $post_errors = Array();
        $post_success = Array();

        try {
	        //post variables
	        if ( isset($_POST['email']) ) {
	            //email input is not disabled and posted
	            $col_email = $_POST['email'];
	        } else {
	            //email is disabled and posted from hidden-email
	            $col_email = $_POST['hidden-email'];
	        }

	        /* ------Front-End Errors------- */

	        //email address is in invalid format
	        if ( !filter_var($col_email, FILTER_VALIDATE_EMAIL) ) { 
	            $post_errors[] = "Invalid email address";
	            $error_email = true;
	        } 

	        /* ------End Front-End Errors------ */


	        if ( sizeof($post_errors) == 0 ) {
	            //No Front-End Errors

	            //generating new confirmation code
	            $col_code = rand ( 1001 , 9999 );  

	            /* -------- updating confirmation code and confimatioinCodeSentOn timestamp and sending an email of new confirmation code to user ------- */

	            //updating code and timestamp 
	            if ( $update_stmt = $mysqli->prepare("UPDATE users SET code = ?, codeConfirmationSentOn = CURRENT_TIMESTAMP WHERE email=?") ) {
	                        
	                $update_stmt->bind_param('is', $col_code, $col_email );
	                $update_status = $update_stmt->execute();
	                if ( $update_status === FALSE ) {
	                    $update_stmt->close();
	                    //error occured
	                    // error_log(">>> EXECUTE Error: " . $update_stmt->error);
	                } elseif ($mysqli->affected_rows == 0) {
	                	$update_stmt->close();
	                    $post_errors[] = "Email address does not exist!";
	                    $error_email = true;
	                } else {
	                    
	                    //sending confirmation code email
	                    $update_stmt->close();
	                    $_SESSION['email'] = $col_email;
	                    require_once 'inc/email_confirm_emailaddress.php';
	                    email_confirm_emailaddress($col_email, $col_code);
	                    header("Location: confirmemail.php");
	                }
	            }   
	        }
	    } catch ( mysqli_sql_exception $me ) {
            header("Location: system-error.php");
        } catch ( Exception $e ) {
            header("Location: system-error.php");
        }
    } 
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Secure Programming Assignment 3</title>
		<link rel="stylesheet" type="text/css" href="assets/css/security.css">
	</head>
	<body class="confirm-body">
		<form id="confirm-form" action="confirmemail.php" method="POST" autocomplete="off">
            <?php 
            if ( isset($post_errors) && (sizeof($post_errors)) ) {
                include("inc/display-error-banner.php");
            }
            if ( isset($success_resend) && $success_resend ) {
                include("inc/display-success-banner.php");
            } 
            // if ( !isset($_SESSION['user_id']) ) {
            //     echo '<p>A validation Code has been emailed on your Registered Email. Please Enter it here.</p>';
            // }
            // else {
            //     echo '<p>A validation Code has been emailed to you. Please Enter it here.</p>';
            // } ?>

            <a class="home-link" href="index.html">SecureURCode</a>
			<h1>Confirm Your Email</h1>
            <h2>Please enter the confirmation code emailed to you!</h2>
			<div class="confirm-form-group">
				<label>Confirm Email Address</label>
				<input type="email" name="email" minlength="7" <?php if (isset($_SESSION['email'])) echo " value='".$_SESSION['email']."' disabled "; ?> required>
			</div>
			<?php 
			if (isset($_SESSION['email'])) {
                echo '<input type="hidden" name="hidden-email" id="hidden-email" value="'. $_SESSION['email'].'">';
            } ?>
			<div class="confirm-form-group">
				<label>Validation Code</label>
				<input type="text" id="confirmation-code" name="confirmation-code" pattern="[0-9]{4}" maxlength="4" minlength="4" required title="Please enter the confirmation code emailed to you!" required>
			</div>
			<div class="confirm-form-group">
				<button type="submit" id="validate" name="validate">Validate</button>
			</div>
			<div class="confirm-form-group">
                <h3>Did not receive the code?</h3>
                <button class="abutton" type="submit" name="resend" id="resend">Resend Code</button>
            </div>
		</form>
		
	</body>
</html>
 				