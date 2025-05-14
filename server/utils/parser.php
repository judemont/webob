<?php

function parseUrl($url) {
    
    $parsedUrl = parse_url($url);

    $domain = $parsedUrl['host'];
    $domain = strtolower(trim($domain));

    $path = $parsedUrl['path'];

    if (substr($path, -1) == "/") {
        $path = substr($path, 0, -1);
    }
    $path = strtolower(trim($path));

    $host = $parsedUrl['host'];
    $host = preg_replace('/^www\./', '', $host);



    return array('domain' => $domain, 'path' => $path);
}