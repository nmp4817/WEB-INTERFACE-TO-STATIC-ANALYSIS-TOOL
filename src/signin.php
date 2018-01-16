<?php
    require_once 'inc/mysql1.php';
    require_once 'inc/encrypt_decrypt_data.php';
    // TODO: Need to iplement the remember me functionality from here 
    // https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence#title.2
                    
    session_set_cookie_params ( 0 );
    session_start();

    $returnto="";

    if ( isset($_GET['returnto']) && !empty($_GET['returnto']) ) {
        $returnto = "?returnto=".$_GET['returnto'];
    }

    if( isset($_SESSION['access_level']) && isset($_SESSION['user_id']) ) {
        if ( isset($_GET['returnto']) && !empty($_GET['returnto']) ) {
            header("Location: ".$_GET['returnto']);
        } else {
            header("Location: home.php");
        }
    } elseif (isset($_POST['signin'])) {

        $col_email = strtolower($_POST['email']);
        $col_password = $_POST['pwd'];

        $post_errors = Array();

        try {
            if ( !filter_var($col_email, FILTER_VALIDATE_EMAIL) ) { 
                $post_errors[] = "Invalid Email Fromat!";
                $error_email = true;
            }
            if ( strlen($col_password) < 6) {
                $post_errors[] = "Invalid Password Format!";
                $error_password = true;
            }
           
            if (sizeof($post_errors) == 0) {
                
                $col_password = encrypt_decrypt_data("encrypt",$col_password);
                
                if ( $select_stmt = $mysqli->prepare("SELECT idCrypto, pwd, accessLevel, displayName from users WHERE email = ?")) {
                   
                    $select_stmt->bind_param("s", $col_email);
                    $select_stmt->execute();
                    $select_stmt->bind_result($idCrypto, $pwd, $accessLevel, $display_name);
                    $select_stmt->fetch();
                    $select_stmt->close();
                    
                    if ( $idCrypto == NULL ) {
                        $post_errors[] = "Email does not exist!.";
                    } elseif( $pwd != $col_password ) {
                        $post_errors[] = "Password is incorrect!.";
                    } elseif($accessLevel == '0') {
                        
                        $_SESSION['email'] = $col_email;
                        $post_errors[] = 'You have already registered.Email is not Confirmed.<a class="alert-link" href="confirmemail.php ">Click Here to confirm!</a>';
                    } else {
                        $_SESSION['user_id'] = $idCrypto;
                        $_SESSION['access_level'] = $accessLevel;
                        $_SESSION['display_name'] = $display_name;

                        if ( isset($_GET['returnto'])) {
                            header("Location: ".$_GET['returnto'] );            
                        } else{
                            header("Location: home.php");
                        }
                    }
                }
            }
        }  catch ( mysqli_sql_exception $me ) {
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
	<body class="signin-body">
		<form id="signin-form" action="signin.php<?php echo $returnto; ?>" method="POST">
            <?php
            if (isset($post_errors) && (sizeof($post_errors))) {
                include("inc/display-error-banner.php");
            } ?>
            <a class="home-link" href="index.html">SecureURCode</a>
			<h1>SignIn</h1>
            <h2>New User? <a style="font-size: 15px; margin: 0px;" class="abutton" href="signup.php">Sign Up</a></h2>
			<div class="signin-form-group">
				<label>UserName</label>
				<input type="email" name="email" minlength="7" <?php if( isset($col_email) ) echo 'value="'.$col_email.'"'; ?> required>
			</div>
			<div class="signin-form-group">
				<label>Password</label>
				<input type="password" name="pwd" pattern="^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=])(?=\S+$).{8,}$" required title="8 characters minimum.&#10;At least one Special character.&#10;At leasr one Uppercase.&#10;At least one lowecase and.&#10;At least one numeric value." required>
			</div>
			<div class="signin-form-group">
				<button type="submit" id="signin" name="signin"> Log in</button>
			</div>
		</form>
		
	</body>
</html>