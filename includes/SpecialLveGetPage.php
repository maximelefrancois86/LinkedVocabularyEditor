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

		$prefix = $this->getRequest()->getText("vocabulary");
		$accept = $this->getRequest()->getText("accept");

		// echo getallheaders()['Accept'];
		//		echo split(',', getallheaders()['Accept']);

		if ($accept === "ttl") {
			$uri = RdfNamespace::namespaces()[$prefix];

			$q = "CONSTRUCT { ?x ?p ?o } FROM <$uri> WHERE { ?x ?p ?o }";
			$store = Arc2Store::getStore();
			$rs = $store->query($q);

			$conf = array('ns' => RdfNamespace::namespaces());

			// $this->getOutput()->disable();
			// Cancel output buffering and gzipping if set
			// This should provide safer streaming for pages with history
			// wfResetOutputBuffers();

			$ser = \ARC2::getTurtleSerializer($conf);
			$request->response()->header("Content-type: text/turtle; charset=utf-8");

			echo $ser->getSerializedIndex($rs['result']);
			exit;
		} else if ($accept === "html") {
			$this->getOutput()->disable();
			wfResetOutputBuffers();
			$msg = '<html><head>
			<script>
			i = window.location.href.lastIndexOf("/");
			start = window.location.href.substring(0,i);
			prefix = "' . $prefix . '";
			fragment = window.location.hash.substring(1);

			window.location.replace(start + "index.php?title=Resource:"+prefix+":"+fragment);

			</script>
			</head>
			<body>redirection page</body></html>';
			echo $msg;
			exit;
		} else {
			$this->getOutput()->addWikitext("This is a redirection page. Use URL parameter 'vocabulary' ''v'' and a fragment ''f'' to redirect to [[Resource:v:f]].
				For instance, [Special:GetResource?vocabulary=seas&accept=html#Class] redirects to [[Resource:rdfs:Class]]");
		}

	}

}