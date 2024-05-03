<?php

require_once MVT::$view_dir . 'navbar.php';

$context = [
    'navbar_items' => $navbar_items,
    'logged_in' => MVT::$auth
];

(new Template('home.html'))->render($context);
