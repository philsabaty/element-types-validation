<?php

function dateFormatToRegexPattern($dateFormat){
    $pattern = $dateFormat;
    $pattern = str_replace(array('%d', '%m', '%t', '%y', '%h', '%H', '%i', '%s'), '\d{2}', $pattern);
    $pattern = str_replace(array('%Y', '%o'), '\d{4}', $pattern);
    $pattern = str_replace(array('%j', '%n', '%g', '%G', '%W'), '\d{1,2}', $pattern);
    $pattern = str_replace(array('%D', '%l', '%f', '%a', '%A'), '\w\w+', $pattern);
    $pattern = str_replace('/', '\/', $pattern);

    return $pattern;
}