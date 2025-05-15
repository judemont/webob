<?php
include_once(dirname(__FILE__) . "/../utils/secrets.php");
include_once(dirname(__FILE__) . "/../utils/parser.php");
include_once(dirname(__FILE__) . "/../utils/database.php");



$userPass = $_POST['password'];
if ($userPass !== PASSWORD) {
    echo json_encode(array("status" => "error", "message" => "InvalID password"));
    exit();
}


$db = new Database();

$url = $_POST['url'];

$parsedUrl = parseUrl($url);

$domain = $parsedUrl['domain'];
$path = $parsedUrl['path'];

$domainInDb = $db->select("SELECT * FROM webob_sites WHERE domain = '$domain'");

if (count($domainInDb) > 0) {
    $siteId = $domainInDb[0]['ID'];
} else {
    echo json_encode(array("status" => "error", "message" => "Domain not found"));
    exit();
}

$pageInDb = $db->select("SELECT * FROM webob_pages WHERE site_ID = '$siteId' AND page = '$path'");
if (count($pageInDb) > 0) {
    $pageId = $pageInDb[0]["ID"];
} else {
    echo json_encode(array("status" => "error", "message" => "Page not found"));
    exit();
}

$db.query("DELETE FROM webob_pages WHERE page = '$path' AND site_ID = '$siteId'");
$db.query("DELETE FROM webob_queue WHERE url = '$url'");
$db.query("DELETE FROM webob_pages_words WHERE page_ID = '$pageId'");