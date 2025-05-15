<?php


include_once(dirname(__FILE__) . "/../utils/secrets.php");
include_once(dirname(__FILE__) . "/../utils/parser.php");
include_once(dirname(__FILE__) . "/../utils/database.php");
include_once(dirname(__FILE__) . "/../utils/encoder.php");


$userPass = $_POST['password'];
if ($userPass !== PASSWORD) {
    echo json_encode(array("status" => "error", "message" => "InvalID password"));
    exit();
}


$db = new Database();


// $queue = $db->select("SELECT * FROM webob_queue ORDER BY priority DESC LIMIT 500");
// $nextItem = $queue[array_rand($queue)];
// $nextUrl = $nextItem['url'];
// echo json_encode(array("status" => "ok", "next" => $nextUrl));


$url = $_POST['url'];
$contents = json_decode($_POST['contents']);
$links = json_decode($_POST['links']);

$parsedUrl = parseUrl($url);
$domain = $parsedUrl['domain'];
$path = $parsedUrl['path'];

$parsedConpleteUrl = "https://" . $domain . $path;

$urlInQueue = $db->select("SELECT * FROM webob_queue WHERE url = '$parsedConpleteUrl'");
if (count($urlInQueue) > 0) {
    $db->query("DELETE FROM webob_queue WHERE url = '$parsedConpleteUrl'");
}


$domainInDb = $db->select("SELECT * FROM webob_sites WHERE domain = '$domain'");

if (count($domainInDb) > 0) {
    $siteId = $domainInDb[0]['ID'];
} else {
    $db->query("INSERT INTO webob_sites (domain) VALUES ('$domain')");
    $siteId = $db->getLastInsertedID();
}

$pageInDb = $db->select("SELECT * FROM webob_pages WHERE site_ID = '$siteId' AND page = '$path'");

$updateContent = true;

if (count($pageInDb) > 0) {
    $pageId = $pageInDb[0]["ID"];
    $last_visited = $pageInDb[0]["last_visited"];
    $now = time();

    if ($last_visited < $now - 2592000) // 30 days
    {
        $db->query("UPDATE webob_pages SET last_visited = '$now' WHERE ID = '$pageId'");
    }else {
        $updateContent = false;
    }

    if (true) {
        $db->query("UPDATE webob_pages SET score = score + 1 WHERE ID = '$pageId'");
    }
} else {
    $db->query("INSERT INTO webob_pages (site_ID, page, score) VALUES ('$siteId', '$path', 1)");
    $pageId = $db->getLastInsertedID();
}





if ($updateContent) {
    $db->query("DELETE FROM webob_pages_words WHERE page_ID = '$pageId'");
    foreach ($contents as $content) {
        $text = $content[0];
        $score = $content[1];

        $encodedWords = encodeText($text);

        foreach ($encodedWords as $encodedWord) {
            $db->query("INSERT INTO webob_pages_words (page_ID, word_ID, score) VALUES ('$pageId', '$encodedWord', '$score')");
        }
    }
}



foreach ($links as $link) {
    $addInQueue = true;

    $parsedLink = parse_url($link);
    $linkDomain = $parsedLink['domain'];
    $linkPath = $parsedLink['path'];

    $linkInDb = $db->select("SELECT * FROM webob_sites WHERE domain = '$linkDomain'");
    if (count($linkInDb) > 0) {
        $linkSiteId = $linkInDb[0]['ID'];
        $pageInDB = $db->select("SELECT * FROM webob_pages WHERE site_ID = '$linkSiteId' AND page = '$linkPath'");
        if (count($pageInDB) > 0) {
            $linkPageId = $pageInDB[0]['ID'];
            $linkPageLastVisited = $pageInDB[0]['last_visited'];

            $now = time();

            if ($linkPageLastVisited < $now - 2592000) 
            {
                $addInQueue = false;
            }
        } 
    }

    $linkInQueue = $db->select("SELECT * FROM webob_queue WHERE url = '$link'");

    if (count($linkInQueue) > 0) {
        $linkId = $linkInQueue[0]['ID'];
        $linkPriority = $linkInQueue[0]['priority'] + 1;
        
        $db->query("UPDATE webob_queue SET priority = $linkPriority WHERE ID = '$linkId'");
    } else {
        $db->query("INSERT INTO webob_queue (url, priority) VALUES ('$link', 1)");
    }
}


