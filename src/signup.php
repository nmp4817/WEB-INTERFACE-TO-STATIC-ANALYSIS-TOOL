<?php
	// TODO: Use mysqli->real_escape_string to get protection against sql injection 

    require_once 'inc/mysql1.php';

    require_once 'inc/encrypt_decrypt_data.php';

    session_start();

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    //date_default_timezone_set('America/Chicago');

    if ( isset($_POST['signup'])) {

        //register button is clicked

        //array to store error messages
        $post_errors = Array();
        try {
            //post variables
            $col_displayName = $_POST['displayName'];
            $col_email = $_POST['email'];
            $col_password = $_POST['pwd'];
            $col_password2 = $_POST['cpwd'];

            /* ------Front-End Errors------- */

            //name is less than 4 char
            if ( strlen($col_displayName) <= 3) {
                $post_errors[] = "Enter you correct name";
                $error_name = TRUE;
            }
            //password is less than 6 char
            if ( strlen($col_password) < 6) {
                $post_errors[] = "Password must be atleast 6 characters";
                $error_password = TRUE;
            }
            //password and confirm-password does not match
            if ($col_password != $col_password2) {
                $post_errors[] = "Both passwords do not match";
                $error_password_ = TRUE;
            }
            //email address is in invalid format
            if ( !filter_var($col_email, FILTER_VALIDATE_EMAIL) ) { 
                $post_errors[] = "Invalid email address";
                $error_email = TRUE;
            }
            // terms and conditions has not been accepted
            if ( empty($_POST["terms"]) || $_POST['terms'] == FALSE ) {
                $post_errors[] = "Please view and accept the terms and conditions";
                $error_terms = TRUE;
            } 

            /* ------End Front-End Errors------ */


            if ( sizeof($post_errors) == 0 ) {
                
                //No Front-End Errors

                //generating confirmation code
                $col_code = rand ( 1001 , 9999 );

                /* -------encrypting password-------- */
                $col_password = encrypt_decrypt_data("encrypt",$col_password);
     
                /* ------Checking if the user is already there? - if yes and accessLevel<100 resend code and update users record else show message user already exists; else insert users record.------- */

                //checking email is already there
                if ( $select_stmt = $mysqli->prepare("SELECT id, accessLevel FROM users WHERE email = ?")) {

                    $select_stmt->bind_param("s", $col_email);
                    $select_stmt->execute();
                    $select_stmt->bind_result($result_id, $accessLevel);
                    $select_stmt->fetch();
                    $select_stmt->close();
                }

                //Transaction Started
                $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

                if ( $result_id == NULL ) {

                    //user does not exist, insert new record for user
                    if ( $insert_stmt = $mysqli->prepare("INSERT INTO users (email, displayName, pwd, code, accessLevel, codeConfirmationSentOn, termsVersionAccepted) VALUES (?,?,?,?,0,CURRENT_TIMESTAMP, ?)") ) {
                    	$col_terms = $_POST['terms'] == 'on' ? 1 : 0; 
                        $insert_stmt->bind_param("sssis",$col_email,$col_displayName,$col_password,$col_code, $col_terms);
                        $insert_status = $insert_stmt->execute();

                        if ( $insert_status === FALSE ) {

                            //error occured
                            // error_log(">>> EXECUTE Error: " . $insert_stmt->error);
                            $insert_stmt->close();
                        } else {
                            $insert_stmt->close();
                            //last inserted id 
                            $id = $mysqli->insert_id;
                
                            //encrypting last inserted user id to make idCrypto
                            $col_idCrypto = encrypt_decrypt_data("encrypt",$id);
                            
                            //updating last inserted record for idCrypto
                            if ( $update_stmt = $mysqli->prepare("UPDATE users SET idCrypto = ? WHERE id=?") ) {

                                $update_stmt->bind_param('si', $col_idCrypto, $id );
                                $update_status = $update_stmt->execute();
                                if ( $update_status === FALSE ) {

                                    //error occured
                                    // error_log(">>> EXECUTE Error: " . $update_stmt->error);
                                    $mysqli->rollback();
                                    $update_stmt->close();
                                } else {
                                    $update_stmt->close();
                                    //sending confirmation code email
                                    $_SESSION['email'] = $col_email;
                                    require_once 'inc/email_confirm_emailaddress.php';
                                    email_confirm_emailaddress($col_email, $col_code);
                                    $mysqli->commit();
                                    header("Location: confirmemail.php");
                                }
                            }
                        }
                    }
                } elseif($accessLevel < 100) {

                    if ( $update_stmt = $mysqli->prepare("UPDATE users SET code = ?, displayName = ?, pwd = ?, codeConfirmationSentOn = CURRENT_TIMESTAMP, termsVersionAccepted = ? WHERE id=?") ) {
                        
                        $update_stmt->bind_param('issis', $col_code, $col_displayName, $col_password, $result_id, $_POST["terms-version"] );
                        $update_status = $update_stmt->execute();
                        if ( $update_status === FALSE ) {
                            $update_stmt->close();
                            //error occured
                            // error_log(">>> EXECUTE Error: " . $update_stmt->error);
                        } else {
                            $update_stmt->close();
                            //sending confirmation code email
                            $_SESSION['email'] = $col_email;
                            require_once 'inc/email_confirm_emailaddress.php';
                            email_confirm_emailaddress($col_email, $col_code);
                            $mysqli->commit();
                            header("Location: confirmemail.php");
                        }
                    }
                }  else {
                    $post_errors[] = "This email address is already registered!";
                    $error_email = true;
                }
            }
        } catch ( mysqli_sql_exception $me ) {
            $mysqli->rollback();
            header("Location: system-error.php");
        } catch ( Exception $e ) {
            $mysqli->rollback();
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
	<body class="signup-body">
		<form id="signup-form" action="signup.php" method="POST">
			<?php
            if (isset($post_errors) && (sizeof($post_errors))) {
                include("inc/display-error-banner.php");
            } ?>
			<a class="home-link" style="text-decoration: none;" href="index.html">SecureURCode</a>
			<h1>Registration</h1>
			<h2>If you already have an account then please sign in. <a style="font-size: 15px; margin: 0px;" class="abutton" href="signin.php">Log In</a></h2>
			<div class="signup-form-group">
				<label style="margin-left: 20px;">Legal Name*</label>
				<input type="text" name="displayName" minlength="3" maxlength="100" pattern="^[-\s\w]*$" <?php if( isset($col_displayName) ) echo 'value="'.$col_displayName.'"'; ?> required>
			</div>
			<div class="signup-form-group">
				<label style="margin-left: 20px;">Email*</label>
				<input type="email" name="email" minlength="7" <?php if( isset($col_email) ) echo 'value="'.$col_email.'"'; ?> required>
			</div>
			<div class="signup-form-group">
				<label>Password*</label>
				<input type="password" name="pwd" pattern="^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=])(?=\S+$).{8,}$" required title="8 characters minimum.&#10;At least one Special character.&#10;At leasr one Uppercase.&#10;At least one lowecase and.&#10;At least one numeric value.">
			</div>
			<div class="signup-form-group">
				<label>Confirm Password*</label>
				<input type="password" name="cpwd" pattern="^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=])(?=\S+$).{8,}$" required title="8 characters minimum.&#10;At least one Special character.&#10;At leasr one Uppercase.&#10;At least one lowecase and.&#10;At least one numeric value.">
			</div>
			
			<input type="checkbox" id="terms" name="terms">
		  	<label id="label_terms" for="terms">
		  		I agree with the <a href="/pages/terms-and-conditions" target="_blank">terms and conditions</a>.
		  	</label>
            
			<div class="signup-form-group">
				<button type="submit" id="signup" name="signup"> Register</button>
			</div>
		</form>
	</body>
</html>