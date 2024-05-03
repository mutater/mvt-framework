<?php

require_once MVT::$view_dir . 'navbar.php';

$context = [
    'navbar_items' => $navbar_items
];

(new Template('auth-required.html'))->render($context);
