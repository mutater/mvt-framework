<?php

require_once 'tools.php';
require_once 'MVT.php';

class NotFoundException extends Exception {}

class Template {
    public static $template_dir = '';
    private $file = '';

    // Instance Methods //

    public function __construct($template_file) {
        $path = self::$template_dir . $template_file;
        if (file_exists($path)) {
            $this->file = file_get_contents($path);
        } else {
            throw new NotFoundException('No template file was found at the path "' . $template_file . '"');
        }
    }

    public function get_template() {
        return $this->file;
    }

    public function get_render($context=null) {
        $render = self::replace_inline_function($this->file, $context);
        $render = self::replace_if($render, $context);
        $render = self::replace_for($render, $context);
        $render = self::replace($render, $context);
        return $render;
    }
    
    public function render($context=null) {
        print($this->get_render($context));
    }

    // Static Methods //

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
        $words = get_clean_words($matches[1]);
        $blockString = $matches[2];
        $var_name = $words[1];
        $key = $words[3];

        $blockRender = "";
        foreach ($context[$key] as $var) {
            $blockRender .= self::replace($blockString, [$var_name => $var]);
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
            fn ($matches) => self::replace_inline_function_callback($matches, $context),
            $template);
    }

    public static function replace_inline_function_callback($matches, $context) {
        $words = get_clean_words($matches[1]);
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
            return self::process_word($words[0]);
        } else {
            return '{% ' . $matches[1] . ' %}';
        }
    }

    public static function replace($template, $context) {
        return preg_replace_callback(
            '/{{\s*(.*?)\s*}}/',
            fn ($matches) => self::replace_callback($matches, $context),
            $template);
    }

    public static function replace_callback($matches, $context) {
        if (is_array($matches)) {
            $matches = $matches[1];
        }

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
