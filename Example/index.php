<?php

require_once __dir__ . '/mvt/MVT.php';

MVT::$USE_SESSIONS = true; // Allow use of sessions
MVT::$USE_AUTHENTICATION = true; // Allow use of authentication on pages
MVT::$USE_ERROR_MESSAGE = true; // Allow showing error messages

MVT::init(__dir__); // Initialize the MVT

MVT::set_routes([ // Set the URL routes
    Route::new('/', 'home.php'),
    Route::new('/about', 'about.php'),
    Route::new('/login', 'login.php'),
    Route::new('/logout', 'logout.php'),
    Route::new('/auth-required', 'auth-required.php')->with_auth() // This page requires authentication
]);

MVT::$auth_redirect_url = "/login"; // Set the URL for redirecting unauthenticated users

MVT::$auth = isset($_SESSION['logged_in']); // Set if the client is authenticated

MVT::process(); // Process the request
