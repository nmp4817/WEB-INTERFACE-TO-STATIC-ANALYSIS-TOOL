<?php
    require_once 'inc/mysql1.php';
    session_start();

    if ( $select_stmt = $mysqli->prepare("SELECT name,type FROM codefiles WHERE idCrypto = ?")) {

        $select_stmt->bind_param("s", $_SESSION['user_id']);
        $select_stmt->execute();
        $select_stmt->bind_result($filename,$type);
        $select_stmt->fetch();
        $select_stmt->close();

        $result_dir = "downloads/";
        $question_dir = "uploads/";

        $result_file = $result_dir.$filename.'.txt';
        $question_file = $question_dir.$filename.".".$type;

        header("Content-disposition: attachment;filename=$result_file");
        header("Content-type: text");
        readfile("$result_file");

        unlink($question_file);
        unlink($result_file);

    }
?>