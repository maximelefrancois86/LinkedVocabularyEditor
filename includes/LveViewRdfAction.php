<?php

class ViewRdfAction extends ViewAction {

	function show() {
		if ($this->getTitle()->getNamespace() == NS_RESOURCE) {

			$request = $this->getRequest();
			$config = $this->context->getConfig();

			if (!$request->checkUrlExtension()) {
				return;
			}

			$about = Lve\Resource::getByTitle($this->getTitle());
			$guri = $about->getUri();

			$ctypes = array(
				"application/rdf+xml" => array("getRDFXMLSerializer", "rdf"), // need to be escaped in URL : application/rdf&2bxml
				"text/turtle" => array("getTurtleSerializer", "ttl"),
				"application/n-triples" => array("getNTriplesSerializer", "n3"),
			);
			$aheader = $request->getAllHeaders()['ACCEPT'];
			$aurl = $request->getVal('accept');
			foreach ($ctypes as $ctype => $config) {
				list($serializer, $extension) = $config;
				if ((strpos($aheader, $ctype) !== false) || (strpos($aurl, $ctype) !== false)) {

					$q = "CONSTRUCT { ?x ?p ?o } FROM <$guri> WHERE { ?x ?p ?o }";
					$store = \Lve\Arc2Store::getStore();
					$rs = $store->query($q);
					if (count($rs['result']) !== 0) {

						$name = substr(lcfirst($this->getTitle()->getText()), 0, -1);
						$filename = $name . '.' . $extension;

						$this->getOutput()->disable();
						$conf = array('ns' => \EasyRdf\RdfNamespace::namespaces());
						$ser = \ARC2::$serializer($conf);
						$request->response()->header("Content-type: $ctype; charset=UTF-8");
						$request->response()->header("Content-disposition: filename=$filename");
						echo $ser->getSerializedIndex($rs['result']);
						exit;
					}
				}
			}
		}
		parent::show();
	}

}
