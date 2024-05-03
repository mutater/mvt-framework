<?php

if (MVT::$is_post and isset(MVT::$request_data)) {
    if (MVT::$request_data['logout'] == 'true') {
        session_destroy();
    }
    
    MVT::redirect('/');
} else {
    require_once MVT::$view_dir . 'navbar.php';

    (new Template('logout.html'))->render(['navbar_items' => $navbar_items]);
}
