<?php

include_once(__DIR__ . "/../utils/secrets.php");
include_once(__DIR__ . "/../utils/parser.php");
include_once(__DIR__ . "/../utils/database.php");
include_once(__DIR__ . "/../utils/encoder.php");

if (!isset($_POST['password'], $_POST['url'], $_POST['contents'], $_POST['links'])) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit();
}

if ($_POST['password'] !== PASSWORD) {
    echo json_encode(["status" => "error", "message" => "Invalid password"]);
    exit();
}

$db = new Database();

$url = $_POST['url'];
$contents = json_decode($_POST['contents']);
$links = json_decode($_POST['links']);

$parsedUrl = parseUrl($url);
$domain = $parsedUrl['domain'];
$path = $parsedUrl['path'];
$parsedCompleteUrl = "https://{$domain}{$path}";
$now = time();

if ($db->exists("SELECT 1 FROM webob_queue WHERE url = ?", [$parsedCompleteUrl])) {
    $db->query("DELETE FROM webob_queue WHERE url = ?", [$parsedCompleteUrl]);
}

$domainInDb = $db->select("SELECT ID FROM webob_sites WHERE domain = ?", [$domain]);
$siteId = $domainInDb[0]['ID'] ?? null;

if (!$siteId) {
    $db->query("INSERT INTO webob_sites (domain) VALUES (?)", [$domain]);
    $siteId = $db->getLastInsertedID();
}

$pageInDb = $db->select("SELECT ID, last_visited FROM webob_pages WHERE site_ID = ? AND page = ?", [$siteId, $path]);
$updateContent = true;

if ($pageInDb) {
    $pageId = $pageInDb[0]["ID"];
    $lastVisited = $pageInDb[0]["last_visited"];

    if ($lastVisited < $now - 2592000) {
        $db->query("UPDATE webob_pages SET last_visited = ? WHERE ID = ?", [$now, $pageId]);
    } else {
        $updateContent = false;
    }

    $db->query("UPDATE webob_pages SET score = score + 1 WHERE ID = ?", [$pageId]);
} else {
    $db->query("INSERT INTO webob_pages (site_ID, page, score) VALUES (?, ?, 1)", [$siteId, $path]);
    $pageId = $db->getLastInsertedID();
}

$db->query(
    "UPDATE webob_sites SET score = (SELECT AVG(score) FROM webob_pages WHERE site_ID = ?) WHERE ID = ?",
    [$siteId, $siteId]
);

if ($updateContent && is_array($contents)) {
    $db->query("DELETE FROM webob_pages_words WHERE page_ID = ?", [$pageId]);

    foreach ($contents as [$text, $score]) {
        $encodedWords = encodeText($text);
        foreach ($encodedWords as $encodedWord) {
            $db->query("INSERT INTO webob_pages_words (page_ID, word_ID, score) VALUES (?, ?, ?)", [$pageId, $encodedWord, $score]);
        }
    }
}

foreach ($links as $link) {
    $parsedLink = parse_url($link);
    if (!isset($parsedLink['host'], $parsedLink['path'])) {
        continue;
    }

    $linkDomain = $parsedLink['host'];
    $linkPath = $parsedLink['path'];

    $addInQueue = true;
    $linkSite = $db->select("SELECT ID FROM webob_sites WHERE domain = ?", [$linkDomain]);

    if ($linkSite) {
        $linkSiteId = $linkSite[0]['ID'];
        $page = $db->select("SELECT last_visited FROM webob_pages WHERE site_ID = ? AND page = ?", [$linkSiteId, $linkPath]);

        if ($page && $page[0]['last_visited'] >= $now - 2592000) {
            $addInQueue = false;
        }
    }

    $linkInQueue = $db->select("SELECT ID, priority FROM webob_queue WHERE url = ?", [$link]);

    if ($linkInQueue) {
        $linkId = $linkInQueue[0]['ID'];
        $newPriority = $linkInQueue[0]['priority'] + 1;
        $db->query("UPDATE webob_queue SET priority = ? WHERE ID = ?", [$newPriority, $linkId]);
    } elseif ($addInQueue) {
        $db->query("INSERT INTO webob_queue (url, priority) VALUES (?, 1)", [$link]);
    }
}
