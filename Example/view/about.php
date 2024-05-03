<?php

require_once MVT::$view_dir . 'navbar.php';

(new Template('about.html'))->render(['navbar_items' => $navbar_items]);
