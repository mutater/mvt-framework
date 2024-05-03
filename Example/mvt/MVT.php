<?php

require_once 'tools.php';
require_once 'Template.php';

class ForbiddenException extends Exception {}
class MalformedRequestException extends Exception {}

class Route {
    public static $auth_check;

    public $url;
    public $view_path;
    public $auth_required = false;

    public function __construct($url, $view_path) {
        $this->url = $url;
        $this->view_path = $view_path;
        $this->auth_required = false;
    }

    public static function new($url, $view_path) {
        return new self($url, $view_path);
    }

    public static function set_auth_check($auth_check) {
        // TODO - Make sure auth function is valid

        self::$auth_check = $auth_check;
    }
    
    public function with_auth() {
        $this->auth_required = true;
        return $this;
    }
}

class MVT {
    public static $USE_AUTHENTICATION = false;
    public static $USE_SESSIONS = false;
    public static $USE_ERROR_MESSAGE = false;
    
    public static $root_dir;
    public static $local_dir = '';
    public static $view_dir;
    public static $mvt_dir;
    public static $url = '';
    public static $query_params;
    public static $query_string;
    public static $request_method;
    public static $request_headers;
    public static $request_data;
    public static $is_get = false;
    public static $is_post = false;
    public static $is_head = false;
    public static $is_put = false;
    public static $is_delete = false;
    public static $is_options = false;
    public static $auth = false;
    public static $auth_redirect_url;

    private static $initialized = false;
    private static $routes;
    
    public static function init($root_dir) {
        self::$root_dir = $root_dir . '/';
        self::set_view_dir('view/');
        self::set_template_dir('template/');
        self::$mvt_dir = __DIR__ . '/';
        
        self::$local_dir = str_replace("/index.php", "", $_SERVER['SCRIPT_NAME']);
        
        $request = str_replace(self::$local_dir, "", $_SERVER['REQUEST_URI']);
        $request_split = explode('?', $request);

        self::$url = $request;
        self::$query_string = $_SERVER['QUERY_STRING'];
        self::$query_params = [];
        self::$request_method = $_SERVER['REQUEST_METHOD'];
        self::$request_headers = apache_request_headers();

        if (count($request_split) == 2) {
            self::$url = $request_split[0];
            parse_str(self::$query_string, self::$query_params);
        }

        if (self::$request_method == 'POST') {
            self::$is_post = true;
            self::$request_data = $_POST;
        } else if (self::$request_method == 'HEAD') {
            self::$is_head = true;
        } else if (self::$request_method == 'PUT') {
            self::$is_put = true;
            self::$request_data = $_POST;
        } else if (self::$request_method == 'DELETE') {
            self::$is_delete = true;
        } else if (self::$request_method == 'OPTIONS') {
            self::$is_options = true;
        }

        if (self::$USE_SESSIONS) {
            session_start();
        }

        self::$initialized = true;
    }

    // Private methods

    private static function render_error($id, $name, $description, $e=null) {
        self::echo_exception($e);
        require 'error.php';
    }

    private static function get_route($url) {
        if (self::is_valid_route($url)) {
            return self::$routes[$url];
        } else {
            throw new NotFoundException('The URL "' . $url . '" does not exist.');
        }
    }

    private static function is_valid_route($url) {
        if (!isset($url) || $url == null) return false;

        return isset(self::$routes[$url]);
    }

    private static function echo_exception($e) {
        if (isset($e) && self::$USE_ERROR_MESSAGE) {
            echo print_trace($e,'<br>');
        }
    }

    // Public methods
    
    public static function set_routes($routes) {
        self::$routes = [];

        foreach ($routes as $route) {
            self::$routes[$route->url] = $route;
        }
    }
    
    public static function set_view_dir($view_dir) {
        self::$view_dir = self::$root_dir . $view_dir;
    }

    public static function set_template_dir($template_dir) {
        Template::$template_dir = self::$root_dir . $template_dir;
    }

    public static function process() {
        try {
            if (!self::$initialized) {
                throw new Exception('The Request class was not initialized.');
            }

            $route = self::get_route(self::$url);

            if (self::$USE_AUTHENTICATION && $route->auth_required && !self::$auth) {
                if (self::is_valid_route(self::$auth_redirect_url)) {
                    self::redirect(self::get_route(self::$auth_redirect_url)->url);
                    return;
                } else {
                    throw new ForbiddenException('Access was denied and Request::$auth_redirect_url was not set.');
                }
            }
            
            $on_shutdown_flag = true;
            $on_shutdown = function () use ($on_shutdown_flag) {
                if (!$on_shutdown_flag) {
                    return;
                }

                $error = error_get_last();
                if (isset($error) and $error['type'] === E_ERROR) {
                    throw new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
                }
            };

            register_shutdown_function($on_shutdown);

            $view_path = self::$view_dir . $route->view_path;
            if (file_exists($view_path)) {
                require $view_path;
            } else {
                throw new NotFoundException('The required view at "' . $view_path . '" was not found.');
            }

            $on_shutdown_flag = false;
        } catch (MalformedRequestException $e) {
            self::render_error(400, 'Bad Request', 'The server was unable to process the request.', $e);
        } catch (ForbiddenException $e) {
            self::render_error(403, 'Forbidden', 'The client is not authorized to access the requested URL.', $e);
        } catch (NotFoundException $e) {
            self::render_error(404, 'Not Found', 'The requested URL was not found on this server.', $e);
        } catch (Throwable $e) {
            self::render_error(500, 'Internal Server Error', 'The server encountered an unexpected error and was unable to complete the request.', $e);
        }
    }

    public static function redirect($url='/', $query_args=null) {
        header('Location: ' . self::abs_url($url) . ($query_args != null ? '?' . $query_args : ''));
    }
    
    public static function abs_url($url) {
        return self::$local_dir . $url;
    }

    public static function abs_path($path) {
        return self::$root_dir . $path;
    }
}
