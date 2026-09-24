<?php

if (! defined('ABSPATH')) {
    exit;
}


$starts = [];
$brace  = strpos($message, '{');
$brack  = strpos($message, '[');
if (false !== $brace) {
    $starts[] = $brace;
}
if (false !== $brack) {
    $starts[] = $brack;
}
if (empty($starts)) {
    return [];
}

sort($starts);
foreach ($starts as $start) {
    $candidate = trim(substr($message, $start));
    $decoded   = json_decode($candidate, true);
    if (is_array($decoded)) {
        return [
            'data'          => $decoded,
            'clean_message' => trim(substr($message, 0, $start)),
        ];
    }
}

return [];
