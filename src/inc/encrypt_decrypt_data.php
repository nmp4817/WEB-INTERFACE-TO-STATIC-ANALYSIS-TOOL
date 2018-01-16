<?php

	
    function encrypt_decrypt_data( $function, $data ) {
	    	
	    $encrypt_method = "AES-256-CBC";
	    $secret_key = 'mykolachi';
	    $secret_iv = 'ancientkolachi';

	    // hash
	    $key = hash('sha256', $secret_key);
	    
	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);

	    if ( $function == 'encrypt' ) {
	    	$encrypted_data = openssl_encrypt($data, $encrypt_method, $key, 0, $iv);
	    	$encrypted_data = base64_encode($encrypted_data);
	    	return $encrypted_data;
	    } elseif ( $function == 'decrypt' ) {
	    	$decrypted_data = $output = openssl_decrypt(base64_decode($data), $encrypt_method, $key, 0, $iv);
    		return $decrypted_data;
	    }
    	

    }

	// $e_txt =  encrypt_decrypt_data('encrypt',"1234567890");
	// echo $e_txt;
	// $d_txt =  encrypt_decrypt_data('decrypt',"c0JIazYyR0JKVXE1QUxhUW1WT3dkUT09");
	// echo $d_txt;

?>