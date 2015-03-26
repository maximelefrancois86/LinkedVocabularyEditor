<?php

namespace Lve;

use \EasyRdf\RdfNamespace;
use \SpecialPage;

/**
 * inspired from RDFIO, samuel.lampa@gmail.com
 * @author maxime.lefrancois.86@gmail.com
 * @package VE
 */
class GetPage extends SpecialPage {

	function __construct() {
		parent::__construct('GetResource');
	}

	function getGroupName() {
		return 'lve';
	}

	// exemple: purl.org/NET/seas-model#Class
	// exemple: http://localhost/seas/wiki/index.php/Special:GetResource?vocabulary=rdfs&accept=html#Class
	function execute($par) {
		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->prefix = $this->getRequest()->getText("vocabulary");

		$accept = $request->getAllHeaders()['ACCEPT'];

		if (strpos($accept, "text/turtle") !== false) {
			$this->serveRdf("getTurtleSerializer", "text/turtle");
		} else if (strpos($accept, "application/rdf+xml") !== false) {
			$this->serveRdf("getRDFXMLSerializer", "application/rdf+xml");
		} else if (strpos($accept, "application/n-triples") !== false) {
			$this->serveRdf("getNTriplesSerializer", "application/n-triples");
		} else {
			$this->getOutput()->disable();
			$request->response()->header('Status: 303 See Also', false, 303);
			$request->response()->header("Location: /index.php?title=Resource:" . ucfirst($this->prefix) . ":");
			exit;
		}

	}

	function serveRdf($parserName, $contentType) {
		$output = $this->getOutput();
		$request = $this->getRequest();
		$uri = RdfNamespace::namespaces()[$this->prefix];
		$q = "CONSTRUCT { ?x ?p ?o } FROM <$uri> WHERE { ?x ?p ?o }";
		$store = Arc2Store::getStore();
		$rs = $store->query($q);
		$conf = array('ns' => RdfNamespace::namespaces());

		$ser = \ARC2::$parserName($conf);
		$request->response()->header("Content-type: $contentType; charset=utf-8");

		echo $ser->getSerializedIndex($rs['result']);
		exit;
	}

}