<?php

namespace Lve;

require 'databaseSettings.php';
require '../vendor/autoload.php';
require '../includes/LveArc2Store.php';
error_reporting(E_ALL^E_DEPRECATED);

$wgDBprefix = "ws";

$wgARC2StoreConfig = array(
	'db_host' => $wgDBserver,
	'db_name' => $wgDBname,
	'db_user' => $wgDBuser,
	'db_pwd' => $wgDBpassword,
	'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix
);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	return;
}

$json = array();

$rdfs = "http://www.w3.org/2000/01/rdf-schema#";
$label = $rdfs . "label";
$comment = $rdfs . "comment";

$dc = "http://purl.org/dc/elements/1.1/";
$title = $dc . "title";

$qlabels = 'SELECT ?x ?label WHERE { { ?x <' . $label . '> ?label } UNION { ?x <' . $title . '> ?label  } }';
$qcomments = 'SELECT ?x ?comment WHERE { ?x <' . $comment . '> ?comment }';

$store = Arc2Store::getStore();
$rs = $store->query($qlabels);
// print_r($rs);

// print_r($rs["result"]["rows"][0]);
if (!$store->getErrors()) {
	foreach ($rs["result"]["rows"] as $row) {
		if (!isset($qlabel[$row["x"]])) {
			$qlabel[$row["x"]] = array("uri" => $row["x"]);
		}
		$json[$row["x"]]["label"] = $row["label"];
	}
}

$rs = $store->query($qcomments);
if (!$store->getErrors()) {
	foreach ($rs["result"]["rows"] as $row) {
		if (!isset($qlabel[$row["x"]])) {
			$qlabel[$row["x"]] = array("uri" => $row["x"]);
		}
		$json[$row["x"]]["comment"] = $row["comment"];
	}
}
header('Content-Type: application/json');
header('Cache-Control: no-transform,public,max-age=3,s-maxage=3');
echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
