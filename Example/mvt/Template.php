<?php

require_once 'tools.php';
require_once 'MVT.php';

class NotFoundException extends Exception {}

class Template {
    public static $template_dir = '';
    private $file = '';
    private $path = '';

    // Instance Methods //

    public function __construct($template_file, $template_dir=null) {
        if (!isset($template_dir)) {
            $template_dir = self::$template_dir;
        }

        $this->path = $template_dir . $template_file;
        if (file_exists($this->path)) {
            $this->file = file_get_contents($this->path);
        } else {
            throw new NotFoundException('No template file was found at the path "' . $template_file . '"');
        }
    }

    public function get_template() {
        return $this->file;
    }

    public function get_render($context=null) {
        return self::get_render_from_string($this->file, $context);
    }

    public static function get_render_from_string($string, $context=null) {
        $render = self::replace_if($string, $context);
        $render = self::replace_for($render, $context);
        $render = self::replace_inline_function($render, $context);
        $render = self::replace($render, $context);
        return $render;
    }
    
    public function render($context=null) {
        self::render_from_string($this->file, $context);
    }

    public static function render_from_string($string, $context=null) {
        print(self::get_render_from_string($string, $context));
    }

    // Replace Methods //

    public static function process_word($word, $context=null) {
        if (str_quoted($word)) {
            return str_unquote($word);
        } else {
            return self::replace_callback($word, $context);
        }
    }

    public static function replace_for($template, $context) {
        return preg_replace_callback(
            '/{%\s*(for.*?)\s*%}([\S\s]*?){%\s*?end\s*?%}/',
            fn ($matches) => self::replace_for_callback($matches, $context),
            $template);
    }

    public static function replace_for_callback($matches, $context) {
        if (!isset($context)) {
            $url = MVT::$url;
            throw new Exception("Tried to render the template on page '$url' with null context.");
        }
        
        $words = get_clean_words($matches[1]);
        $blockString = $matches[2];
        $var_name = $words[1];
        $key = $words[3];

        $blockRender = "";
        foreach ($context[$key] as $var) {
            $blockRender .= self::get_render_from_string($blockString, [$var_name => $var]);
        }
        return $blockRender;
    }

    public static function replace_if($template, $context) {
        return preg_replace_callback(
            '/{%\s*(if.*?)\s*%}([\S\s]*?){%\s*?end\s*?%}/',
            fn ($matches) => self::replace_if_callback($matches, $context),
            $template);
    }

    public static function replace_if_callback($matches, $context) {
        if (!isset($context)) {
            $url = MVT::$url;
            throw new Exception("Tried to render the template on page '$url' with null context.");
        }
        
        $words = get_clean_words($matches[1]);

        if (self::replace_callback($words[1], $context) === true) {
            return $matches[2];
        } else {
            return '';
        }
    }

    public static function replace_inline_function($template, $context) {
        return preg_replace_callback(
            '/{%\s*(.*?)\s*%}/',
            fn ($matches) => self::replace_inline_function_callback($matches[1], $context),
            $template);
    }

    public static function replace_inline_function_callback($matches, $context) {
        $words = get_clean_words($matches);
        $function = array_get($words, 0, "Bad Function");
        array_shift($words);

        if ($function == 'echo') {
            $string = "";
            
            foreach ($words as $word) {
                $string .= self::process_word($word);
            }

            echo $string;
        } else if ($function == 'include') {
            $template_file = self::process_word($words[0]);
            $template = new self($template_file);
            return $template->get_render($context);
        } else if ($function == 'url') {
            return MVT::abs_url(self::process_word($words[0], $context));
        } else {
            return '{% ' . $matches . ' %}';
        }
    }

    public static function replace($template, $context) {
        return preg_replace_callback(
            '/{{\s*(.*?)\s*}}/',
            fn ($matches) => self::replace_callback($matches[1], $context),
            $template);
    }

    public static function replace_callback($matches, $context) {
        $contextKeys = get_clean_words($matches, '.');
        $contextValue = array_get($context, $contextKeys[0], $matches);
        array_shift($contextKeys);

        if (is_string($contextValue)) {
            return $contextValue;
        }

        foreach ($contextKeys as $key) {
            if (!array_key_exists($key, $contextValue)) {
                return '';
            }

            $contextValue = $contextValue[$key];
        }

        return $contextValue;
    }
}
