<?php

if (! defined('ABSPATH')) {
    exit;
}


$update = false;

if ($price && ($this->thousand_sep != ',' || $this->decimal_sep != '.')) {
    if (strpos($price, $this->thousand_sep) !== false) {
        $parts = explode($this->thousand_sep, $price);

        if (count($parts) > 1) {
            foreach ($parts as $k => $v) {
                if ($this->decimal_sep && strpos($v, $this->decimal_sep) !== false) {
                    $decparts = explode($this->decimal_sep, $v);
                    $parts[$k] = $decparts[0];
                }
            }

            $groupLengths = array_map('strlen', $parts);
            if (max($groupLengths) == 3) {
                $update = true;
                $price = str_replace($this->thousand_sep, '', $price);
            }
        }
    }

    if (strpos($price, $this->decimal_sep) !== false) {
        $update = true;
        $price = str_replace($this->decimal_sep, '.', $price);
    }
}

if ($update) {
    return number_format($price, 9, '.', '');
}

return false;
