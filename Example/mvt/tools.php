<?php

function array_get($array, $key, $default=null) {
    if (!is_array($array)) {
        return $default;
    }

    if (array_key_exists($key, $array)) {
        return $array[$key];
    } else {
        return $default;
    }
}

function get_clean_words($haystack, $needle='\s') {
    $matches = [];
    preg_match_all('/[^' . $needle . '"\']+|("[^"]*")|(\'[^\']*\')/', strtolower($haystack), $matches);
    return $matches[0];
}

function str_surrounded_by($haystack, $needle) {
    return str_starts_with($haystack, $needle) and str_ends_with($haystack, $needle);
}

function str_quoted($haystack) {
    return str_surrounded_by($haystack, '"') or str_surrounded_by($haystack, "'");
}

function str_unquote($str) {
    while (str_quoted($str)) {
        $str = trim($str, '"');
        $str = trim($str, "'");
    }

    return $str;
}

function print_trace($e, $newline='\n', $seen=null) {
    $starter = $seen ? 'Caused by: ' : '';
    $result = array();
    if (!$seen) $seen = array();
    $trace  = $e->getTrace();
    $prev   = $e->getPrevious();
    $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
    $file = $e->getFile();
    $line = $e->getLine();
    while (true) {
        $current = "$file:$line";
        if (is_array($seen) && in_array($current, $seen)) {
            $result[] = sprintf(' ... %d more', count($trace)+1);
            break;
        }
        $result[] = sprintf(' at %s%s%s(%s%s%s)',
                                    count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                                    count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                                    count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                                    $line === null ? $file : basename($file),
                                    $line === null ? '' : ':',
                                    $line === null ? '' : $line);
        if (is_array($seen))
            $seen[] = "$file:$line";
        if (!count($trace))
            break;
        $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
        $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
        array_shift($trace);
    }
    $result = join($newline, $result);
    if ($prev)
        $result  .= $newline . print_trace($prev, $newline, $seen);

    return $result;
}
