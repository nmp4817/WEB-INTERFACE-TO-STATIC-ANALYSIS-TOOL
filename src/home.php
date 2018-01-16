<?php

    require_once 'inc/mysql1.php';
    require_once 'inc/encrypt_decrypt_data.php';

    session_start();

    //session variables 
    $access_level = $_SESSION['access_level'];
    $user_id = $_SESSION['user_id'];

    if (!isset($access_level) || !isset($user_id) ) {
        // User is not logged in    
        header("Location: signin.php?returnto=".basename($_SERVER['PHP_SELF']));
    } else {

        $post_errors = Array();
        $post_success = Array();

        try {
            if (isset($_POST['add'])) {

                $target_dir = "uploads/";
                
                if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES["images"]["name"] as $index=>$name) {
                        
                        // $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                        // Check if image file is a actual image or fake image

                        $fileName = explode(".", $name);
                        $imageFileType = strtolower(end($fileName));
                        //Transaction Started
                        $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

                        if ( $insert_stmt = $mysqli->prepare("INSERT INTO codefiles ( idCrypto, name, type, uploadedOn ) VALUES (?,?,?,CURRENT_TIMESTAMP)") ) {

                            $insert_stmt->bind_param("sss",$_SESSION['user_id'],$fileName[0],$imageFileType);
                            $insert_status = $insert_stmt->execute();
                            
                            if ( $insert_status == FALSE ) {
                                $insert_stmt->close();
                                //echo $insert_stmt->error;
                                // error_log(">>> EXECUTE Error: " . $insert_stmt->error);
                                // $post_errors[] = "Could not insert in ShowingTime!";
                            } else {
                                //last inserted id 
                                $insert_stmt->close();
                                $id = $mysqli->insert_id;

                                //encrypting last inserted listing id to make urlId
                                require_once 'inc/encrypt_decrypt_data.php';

                                $target_file = $target_dir . basename($name);
                                $uploadOk = 1;
                            
                                // Check if file already exists
                                if (file_exists($target_file)) {
                                    $post_errors[] = "Sorry, file already exists.";
                                    $uploadOk = 0;
                                }
                                // Check file size
                                if ($_FILES["images"]["size"][$index] > 500000000) {
                                    $post_errors[] = "Sorry, your file is too large.";
                                    $uploadOk = 0;
                                }
                                // Allow certain file formats
                                if($imageFileType != "java" && $imageFileType != "php" && $imageFileType != "c" && $imageFileType != "cpp" ) {
                                    $post_errors[] = "Sorry, only java, php, c & c++ files are allowed.";
                                    $uploadOk = 0;
                                }
                                
                                // Check if $uploadOk is set to 0 by an error
                                if ($uploadOk == 0) {
                                    $post_errors[] = "Sorry, your file was not uploaded.";
                                    $mysqli->rollback();
                                    // if everything is ok, try to upload file
                                } else {
                                    
                                    if (move_uploaded_file($_FILES["images"]["tmp_name"][$index], $target_file)) {
                                        
                                        $post_success[] = "The file ". basename( $_FILES["images"]["name"][$index]). " has been uploaded.";

                                        $pyscript = "C:\\Users\\t\\Desktop\\SecureProgramming\\Assignments\\Assignment2\\flawfinder\\flawfinder-2.0.4\\flawfinder";
                                        $python = "C:\\Python27\\python.exe";
                                        $filePath = $target_file;

                                        $cmd = "$python $pyscript $filePath > downloads\\$fileName[0].txt";
                                        `$cmd`;

                                        // echo $cmd;
                                        $mysqli->commit();
                                        
                                    } else {
                                        $mysqli->rollback();
                                        $post_errors[] = "Sorry, there was an error uploading your file.";
                                    } 
                                }
                            }
                        } 
                    } 

                    if (sizeof($post_errors) == 0) {
                        header("Location: results.php");
                    }  
                } else {
                    $post_errors[] = "Please select files to upload!.";
                }
            }
        } catch ( mysqli_sql_exception $me ) {
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
        <form id="signin-form" action="home.php" method="POST" enctype="multipart/form-data">
            <?php
            if (isset($post_errors) && (sizeof($post_errors))) {
                include("inc/display-error-banner.php");
            }
            if (isset($post_success) && (sizeof($post_success))) {
                include("inc/display-success-banner.php");
            } ?>
            <a style="border-radius: 12px;background: #4E9CAF;color:white;float: left;" class="home-link" href="signout.php">Logout</a>
            <a class="home-link" href="index.html">SecureURCode</a>
            <h1>Uplod Code Files</h1>
            <div class="signin-form-group">
                <input type="file" name="images[]" id="images[]" multiple="multiple">
                <button type="submit" id="add" name="add">Upload Files</button>
            </div>
        </form>
        
    </body>
</html>