<?php

if (MVT::$is_post and isset(MVT::$request_data)) {
    if (MVT::$request_data['username'] == 'username' && MVT::$request_data['password'] == 'password') {
        $_SESSION['logged_in'] = true;
    } else {
        MVT::redirect('/login');
        exit;
    }
    
    MVT::redirect('/');
} else {
    require_once MVT::$view_dir . 'navbar.php';

    (new Template('login.html'))->render(['navbar_items' => $navbar_items]);
}
