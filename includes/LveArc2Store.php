<?php

namespace Lve;

use EasyRdf\RdfNamespace;

class Arc2Store {

	public static function getStore() {
		global $wgARC2StoreConfig;
		$store = \ARC2::getStore($wgARC2StoreConfig);
		if (!$store->isSetUp()) {
			$store->setUp();
		}
		return $store;
	}

	public static function toTriples($graph) {
		// go through easyrdf serializer and arc2 parser.
		$output = $graph->serialise("turtle");
		if (!is_scalar($output)) {
			$output = var_export($output, true);
		}
		$parser = \ARC2::getTurtleParser();
		$parser->parse($graph->getUri(), $output);
		return $parser->getTriples();
	}

	// this function sets the Easyrdf namespaces according to the ones stored in the database
	public static function readNamespaces() {
		foreach (RdfNamespace::namespaces() as $prefix => $long) {
			RdfNamespace::delete($prefix);
		}
		$namespaces = self::getStore()->getSetting("ns");
		if (null == $namespaces) {
			$namespaces = array();
		}
		foreach ($namespaces as $prefix => $long) {
			RdfNamespace::set($prefix, $long);
		}
	}

	// this function sets the Easyrdf namespaces according to the ones stored in the database
	public static function writeNamespaces() {
		self::getStore()->setSetting("ns", RdfNamespace::namespaces());
	}
}
