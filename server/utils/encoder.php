<?php
include_once(dirname(__FILE__) . "/database.php");


function search($query) {
    $db = new Database();

    $query = strtolower(preg_replace('/[^a-zA-Z0-9]/', ' ', $query));
    $words = explode(" ", $text);
    $reuslt = array();

    $db = new Database();
    foreach ($words as $str) {
        $inDb = $db->select("SELECT * FROM webob_dict WHERE word = '$word'");

        if (count($inDb) <= 0) {
            $db->query("INSERT INTO webob_dict (word) VALUES ('$word')");
            $id = $db->getLastInsertedID();
            $reuslt[] = $id;
        }else{
            $reuslt[] = $inDb[0]['ID'];
        }

    }   

}