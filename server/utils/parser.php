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
    $host2 = preg_replace('/^www\./', '', $host);

    $ip = gethostbyname($host);
    if (!($ip !== $host)) {
        $host = $host2;
    }

    return array('domain' => $domain, 'path' => $path);
}