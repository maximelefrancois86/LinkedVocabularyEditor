<?php

namespace Lve;

use EasyRdf\RdfNamespace;
use ML\JsonLD\Document;
use ML\JsonLD\JsonLD;
use \ParserOptions;
use \ParserOutput;
use \Title;

class JsonldContent extends \JsonContent {

	/**
	 * This constructor computes the expanded version of the input, and uses a text representation of this as model.
	 *
	 **/
	public function __construct($json) {
		$expanded = JsonLD::expand($json);
		self::cleanDocument($expanded);
		$text = JsonLd::toString($expanded, true);
		parent::__construct($text, CONTENT_MODEL_JSONLD);
		// global $wgOut;
		// $wgOut->addInlineScript("console.log('content is '," . json_encode($expanded) . ");");
	}

	private static function objectToArray($obj) {
		if (is_object($obj)) {
			$obj = (array) $obj;
		}
		if (is_array($obj)) {
			$new = array();
			foreach ($obj as $key => $val) {
				$new[$key] = self::objectToArray($val);
			}
		} else {
			$new = $obj;
		}
		return $new;
	}

	public static function cleanDocument(&$expanded) {
		$expanded = self::objectToArray($expanded);
		if (!isset($expanded[0]) || !isset($expanded[0]["@graph"])) {
			$expanded = array(array("@graph" => array()));
		}
		if (isset($expanded[0]["@graph"])) {
			foreach ($expanded[0]["@graph"] as $i => $g) {
				if (count($g["@graph"]) === 0) {
					array_splice($expanded[0]["@graph"], $i, 1);
				}
//				self::cleanGraph($expanded[0]->{"@graph"}, $i, $g);
			}
		}
	}
	public static function cleanGraph(&$gs, $i, &$g) {
		global $wgOut;
		$wgOut->addInlineScript("console.log('cleangraph',$i);");
		if (!isset($g->{"@graph"}) || strpos($g->{"@id"}, "_:") === 0) {
			unset($gs[$i]);
			$wgOut->addInlineScript("console.log('cleangraph unset 1',$i);");
			return;
		}
		foreach ($g->{"@graph"} as $i => $triples) {
			// self::cleanTriples($g->{"@graph"}, $i, $triples);
		}
		if (count((array) $g->{"@graph"}) === 0) {
			unset($gs[$i]);
			$wgOut->addInlineScript("console.log('cleangraph unset 2',$i);");
		}
	}
	public static function cleanTriples(&$s, $i, &$triples) {
		global $wgOut;
		if (!isset($triples->{"@id"})) {
			unset($s[$i]);
			$wgOut->addInlineScript("console.log('clean triples unset 1',$i);");
			return;
		}
		foreach ($triples as $p => $objects) {
			if ($p == "@id") {
				continue 1;
			}
			if (count($objects) == 0) {
				unset($triples->{$p});
				$wgOut->addInlineScript("console.log('clean triples unset object',$p);");
			}
		}
		if (count($triples == 1)) {
			unset($s[$i]);
			$wgOut->addInlineScript("console.log('clean triples unset 2',$i);");
		}
	}

	/**
	 * Beautifies JSON prior to save.
	 * @param Title $title Title
	 * @param User $user User
	 * @param ParserOptions $popts
	 * @return JsonContent
	 */
	public function preSaveTransform(\Title $title, \User $user, \ParserOptions $popts) {
		return $this;
	}

	/**
	 * Decodes the JSON into a JsonLD Document object.
	 * @return Document
	 */
	public function getDocument() {
		return JsonLD::getDocument($this->mText);
	}

	/**
	 * Returns a ParserOutput object resulting from parsing the content's text
	 * using $wgParser.
	 *
	 * @param Title $title
	 * @param int $revId Revision to pass to the parser (default: null)
	 * @param ParserOptions $options (default: null)
	 * @param bool $generateHtml (default: true)
	 * @param ParserOutput &$output ParserOutput representing the HTML form of the text,
	 *           may be manipulated or replaced.
	 */
	protected function fillParserOutput(Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output) {
		global $wgParser, $wgScriptPath;
		$this->about = Resource::getByTitle($title);

		$text = "";
		$text .= "__NOTOC__ __NOEDITSECTION__\n";
		$text .= "===IRI===\n\n";
		$text .= $this->about->getUri() . "\n\n";
		$text .= "===Other visualisation===\n\n";
		$url = $this->about->getUri();
		$url2 = $title->getFullUrl();

		if (strpos($url2, "?title=") !== false) {
			$sep = "&";
		} else {
			$sep = "?";
		}

		$urlrdf = substr($url2, 0, strrpos($url2, ":") + 1) . $sep . 'accept=application/rdf%2bxml#' . substr($url2, strrpos($url2, ":") + 1);
		$urlttl = substr($url2, 0, strrpos($url2, ":") + 1) . $sep . 'accept=text/turtle#' . substr($url2, strrpos($url2, ":") + 1);
		$urln3 = substr($url2, 0, strrpos($url2, ":") + 1) . $sep . 'accept=application/n-triples#' . substr($url2, strrpos($url2, ":") + 1);
		$text .= "Ontology source ([$urlrdf application/rdf+xml], [$urlttl application/turtle], [$urln3 application/n-triples])\n\n";

		if (strlen($url2) === strrpos($url2, ":") + 1) {
			$this->fillAsOntology($text);
		} else {
			$this->fillAsResource($text);
		}

		$output = $wgParser->parse($text, $title, $options, true, true, $revId);

		// $output->addOutputHook(function ($output) {$output->addHTML("<h3>Source</h3><summary><details>Wikipage content as Json-LD<details><pre>" . $this->getNativeData() . "</pre></summary");});

	}

	protected function fillAsOntology(&$text) {
		$guri = $this->about->getUri();
		$this->fillValues($text, "Title", "http://purl.org/dc/terms/title");
		$this->fillValues($text, "Abstract", "http://purl.org/dc/terms/description");
		$this->fillValues($text, "Version Info", "http://www.w3.org/2002/07/owl#versionInfo");
		$this->fillValues($text, "Comment", "http://www.w3.org/2000/01/rdf-schema#comment");
		$this->fillResources($text, "License", "http://creativecommons.org/ns#license");
		$this->fillResourcesType($text, "Ontology Classes", "http://www.w3.org/2002/07/owl#Class");
		$this->fillResourcesType($text, "Ontology Object Properties", "http://www.w3.org/2002/07/owl#ObjectProperty");
		$this->fillResourcesType($text, "Ontology Data Properties", "http://www.w3.org/2002/07/owl#DataProperty");
		$this->fillResourcesType($text, "Other Classes", "http://www.w3.org/2000/01/rdf-schema#Class");
		$this->fillResourcesType($text, "Other Properties", "http://www.w3.org/1999/02/22-rdf-syntax-ns#Property");

	}

	protected function fillResourcesType(&$text, $type, $turi) {
		$guri = $this->about->getUri();
		$q = "SELECT DISTINCT ?x FROM <$guri> WHERE {
			?x a <$turi> .
		} ORDER BY ?x";
		$rs = \Lve\Arc2Store::getStore()->query($q);
		if (count($rs['result']['rows']) >= 1) {
			$text .= "==$type==\n\n";
		}
		foreach ($rs['result']['rows'] as $row) {
			$r = Resource::get($row['x']);
			if ($r->isBNode()) {
				continue;
			}
			$rfragment = $r->getFragment();
			$rfull = $r->getTitle();
			$rpname = $r->getPName();
			$ruri = $r->getUri();

			$q = "SELECT DISTINCT ?o FROM <$guri> WHERE {
				<$ruri> <http://www.w3.org/2000/01/rdf-schema#comment> ?o .
			} ORDER BY ?o";
			$rs = \Lve\Arc2Store::getStore()->query($q);
			$rcomment = (count($rs['result']['rows']) >= 1) ? " - " . $rs['result']['rows'][0]['o'] : "";

			$text .= "* <span id='$rfragment'>[[$rfull|$rpname]] $rcomment </span>\n";
		}

	}

	protected function fillResources(&$text, $title, $puri) {
		$uri = $this->about->getUri();
		$pname = $this->about->getPName();
		$guri = Resource::get(substr($pname, 0, strpos($pname, ":") + 1))->getUri();

		$q = "SELECT DISTINCT ?o FROM <$guri> WHERE {
			<$uri> <$puri> ?o .
		} ORDER BY ?o";
		$rs = \Lve\Arc2Store::getStore()->query($q);

		if (count($rs['result']['rows']) >= 1) {
			$text .= "===$title===\n\n";
		}
		foreach ($rs['result']['rows'] as $row) {
			$this->printResource($text, Resource::get($row['o']));
		}
	}

	protected function fillResourcesReverse(&$text, $title, $puri) {
		$uri = $this->about->getUri();
		$pname = $this->about->getPName();
		$guri = Resource::get(substr($pname, 0, strpos($pname, ":") + 1))->getUri();

		$q = "SELECT DISTINCT ?o FROM <$guri> WHERE {
			?o <$puri> <$uri> .
		} ORDER BY ?o";
		$rs = \Lve\Arc2Store::getStore()->query($q);

		if (count($rs['result']['rows']) >= 1) {
			$text .= "===$title===\n\n";
		}
		foreach ($rs['result']['rows'] as $row) {
			$this->printResource($text, Resource::get($row['o']));
		}
	}

	protected function printResource(&$text, $r) {
		if ($r->isBNode()) {
			return;
		}
		$rfragment = $r->getFragment();
		$rfull = $r->getTitle();
		$rpname = $r->getPName();
		$ruri = $r->getUri();

		$q = "SELECT DISTINCT ?o WHERE {
			<$ruri> <http://www.w3.org/2000/01/rdf-schema#comment> ?o .
		} ORDER BY ?o";
		$rs = \Lve\Arc2Store::getStore()->query($q);
		$rcomment = (count($rs['result']['rows']) >= 1) ? " - " . $rs['result']['rows'][0]['o'] : "";

		if (strpos($rpname, "ns") === 0) {
			$rpname = $ruri;
		}
		$text .= "* <span id='$rfragment'>[$ruri $rpname] $rcomment </span>\n";
	}

	protected function fillAsResource(&$text) {
		$this->fillValues($text, "Human-readable names", "http://www.w3.org/2000/01/rdf-schema#label");
		$this->fillResources($text, "Types", "http://www.w3.org/1999/02/22-rdf-syntax-ns#type");
		$this->fillValues($text, "Human-readable descriptions", "http://www.w3.org/2000/01/rdf-schema#comment");
		$this->fillValues($text, "Term status", "http://www.w3.org/2003/06/sw-vocab-status/ns#term_status");
		$this->fillValues($text, "Additional informations", "http://www.w3.org/2003/06/sw-vocab-status/ns#moreinfos");

		$this->fillResources($text, "Direct super classes", "http://www.w3.org/2000/01/rdf-schema#subClassOf");
		$this->fillResources($text, "Equivalent classes", "http://www.w3.org/2002/07/owl#equivalentClass");
		$this->fillResources($text, "Disjoint classes", "http://www.w3.org/2002/07/owl#disjointWith");
		$this->fillResourcesReverse($text, "Domain Of", "http://www.w3.org/2000/01/rdf-schema#domain");
		$this->fillResourcesReverse($text, "Range Of", "http://www.w3.org/2000/01/rdf-schema#range");

		$this->fillResources($text, "Direct super properties", "http://www.w3.org/2000/01/rdf-schema#subPropertyOf");
		$this->fillResources($text, "Known domains", "http://www.w3.org/2000/01/rdf-schema#domain");
		$this->fillResources($text, "Known ranges", "http://www.w3.org/2000/01/rdf-schema#range");
		$this->fillResources($text, "Known equivalent properties", "http://www.w3.org/2002/07/owl#equivalentProperty");
		$this->fillResources($text, "Known disjoint properties", "http://www.w3.org/2002/07/owl#propertyDisjointWith");
		$this->fillResources($text, "Known inverse properties", "http://www.w3.org/2002/07/owl#inverseOf");

	}

	protected function fillValues(&$text, $title, $puri) {
		$uri = $this->about->getUri();
		$pname = $this->about->getPName();
		$guri = Resource::get(substr($pname, 0, strpos($pname, ":") + 1))->getUri();

		$q = "SELECT DISTINCT ?o FROM <$guri> WHERE {
			<$uri> <$puri> ?o .
		} ORDER BY ?o";
		$rs = \Lve\Arc2Store::getStore()->query($q);

		if (count($rs['result']['rows']) >= 1) {
			$text .= "===$title===\n\n";
		}
		foreach ($rs['result']['rows'] as $row) {
			$olang = isset($row['o lang']) ? " (a rdf:langString, @" . $row['o lang'] . ")" : "";
			$odatatype = isset($row['o datatype']) ? " (a " . RdfNamespace::shorten($row['o datatype']) . ")" : "";
			$text .= "* " . $row['o'] . $olang . $odatatype . "\n";
		}

	}

}
