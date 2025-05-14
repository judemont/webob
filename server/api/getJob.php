<?php
include_once(dirname(__FILE__) . "/../utils/database.php");



$db = new Database();

$queue = $db->select("SELECT * FROM webob_queue ORDER BY priority DESC LIMIT 500");

$nextItem = $queue[array_rand($queue)];
$nextUrl = $nextItem['url'];

echo json_encode(array("status" => "ok", "next" => $nextUrl));