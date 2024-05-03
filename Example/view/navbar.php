<?php

$navbar_items = [
    ['title' => 'Home', 'url' => '/', 'active' => false],
    ['title' => 'About', 'url' => '/about', 'active' => false]
];

if (MVT::$auth) {
    $navbar_items[] = ['title' => 'Auth Required', 'url' => '/auth-required', 'active' => false];
    $navbar_items[] = ['title' => 'Logout', 'url' => '/logout', 'active' => false];
} else {
    $navbar_items[] = ['title' => 'Login', 'url' => '/login', 'active' => false];
}
