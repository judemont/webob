<?php

function parseUrl($url) {
    
    $parsedUrl = parse_url($url);

    $domain = $parsedUrl['host'];
    $domain = strtolower(trim($domain));

    // Extract the path and remove URL parameters
    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
    if (substr($path, -1) == "/") {
        $path = substr($path, 0, -1);
    }
    $path = strtolower(trim($path));

    // Remove query string if present
    $path = explode('?', $path)[0];

    $host = $parsedUrl['host'];
    $host = preg_replace('/^www\./', '', $host);

    return array('domain' => $domain, 'path' => $path);
}