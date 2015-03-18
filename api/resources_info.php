<?php

namespace Lve;
use \EasyRdf\RdfNamespace;

require '../vendor/autoload.php';
require '../includes/LveArc2Store.php';
error_reporting(E_ALL^E_DEPRECATED);

$wgDBprefix = "ws";

$wgDBtype = "mysql";
$wgDBserver = "localhost";
$wgDBname = "wikiseas2";
$wgDBuser = "root";
$wgDBpassword = "root";

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

Arc2Store::readNamespaces();

$rdfs = "http://www.w3.org/2000/01/rdf-schema#";
$label = $rdfs . "label";
$comment = $rdfs . "comment";
$dc = "http://purl.org/dc/elements/1.1/";
$title = $dc . "title";
$description = $dc . "description";

$q = isset($_REQUEST["q"]) ? $_REQUEST["q"] : "";

$query = "SELECT DISTINCT ?x ?label ?comment ?title ?description
WHERE {
?x ?p ?y .
OPTIONAL { ?x <$label> ?label }
OPTIONAL { ?x <$comment> ?comment  }
OPTIONAL { ?x <$title> ?title  }
OPTIONAL { ?x <$description> ?description  }";

if ($q !== "") {
	$query .= "FILTER ( regex(?label, '$q') || regex(?comment, '$q') || regex(?title, '$q') || regex(?description, '$q') )";
}
$query .= "} ORDER BY ?x";

$store = Arc2Store::getStore();
$rs = $store->query($query);

$json = array();
if (!$store->getErrors()) {
	foreach ($rs["result"]["rows"] as $row) {
		$o = array("uri" => $row["x"]);
		$o["pname"] = RdfNamespace::shorten($row["x"]);
		$o["label"] = "";
		$o["comment"] = "";
		if (isset($row["label"])) {
			$o["label"] = $row["label"];
		}
		if (isset($row["comment"])) {
			$o["comment"] = $row["comment"];
		}
		if (isset($row["title"])) {
			$o["label"] = $row["title"];
		}
		if (isset($row["description"])) {
			$o["comment"] = $row["description"];
		}
		$json[] = $o;
	}
}

header('Content-Type: application/json');
header('Cache-Control: no-transform,public,max-age=3,s-maxage=3');
echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
