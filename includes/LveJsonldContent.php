<?php

namespace Lve;

use ML\JsonLD\Document;
use ML\JsonLD\JsonLD;

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

	// /**
	//  * Returns a ParserOutput object resulting from parsing the content's text
	//  * using $wgParser.
	//  *
	//  * @param Title $title
	//  * @param int $revId Revision to pass to the parser (default: null)
	//  * @param ParserOptions $options (default: null)
	//  * @param bool $generateHtml (default: true)
	//  * @param ParserOutput &$output ParserOutput representing the HTML form of the text,
	//  *           may be manipulated or replaced.
	//  */
	// protected function fillParserOutput(Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output) {
	// 	global $wgParser;
	// 	$about = Resource::getByTitle($title);

	// 	// $msg = array();

	// 	// $msg[] = "'''Description of resource " . $about->getPName() . "'''";
	// 	// $msg[] = "URI: " . $about->getExtLink();
	// 	// foreach ($graphs as $guri => $graph) {
	// 	// 	$gextlink = Resource::get($guri)->getExtLink();
	// 	// 	$msg[] = "== Assertions in graph " . $gextlink . "==";
	// 	// 	$s = $graph->resource($about->getPName());
	// 	// 	$props = $s->properties();
	// 	// 	foreach ($props as $p) {
	// 	// 		$msg[] = "* has " . Resource::get($p)->getWikilink() . ":";
	// 	// 		foreach ($s->all($p) as $o) {
	// 	// 			if ($o instanceof \EasyRdf\Resource) {
	// 	// 				$msg[] = "** " . Resource::get($o)->getWikilink();
	// 	// 			} elseif (null !== $o->getLang() && '' !== $o->getLang()) {
	// 	// 				$msg[] = "** in language " . $o->getLang() . ": \"" . $o->__toString() . "\"";
	// 	// 			} elseif (null !== $o->getDatatype() && '' !== $o->getDatatype()) {
	// 	// 				$msg[] = "** \"" . $o->__toString() . "\", with type " . Resource::get($o->getDatatype())->getWikilink();
	// 	// 			} else {
	// 	// 				$msg[] = "** \"" . $o->__toString() . ".";
	// 	// 			}
	// 	// 		}
	// 	// 	}
	// 	// 	foreach ($s->reversePropertyUris() as $p) {
	// 	// 		$msg[] = "* is the " . Resource::get($p)->getWikilink() . " of:";
	// 	// 		foreach ($s->all("^" . \EasyRdf\RdfNamespace::shorten($p)) as $o) {
	// 	// 			$msg[] = "** " . Resource::get($o)->getWikilink() . ".";
	// 	// 		}
	// 	// 	}
	// 	// }

	// 	$text = "Click on 'Edit' to see the description of resource " . $about->getPName() . "\n\n";
	// 	$text .= "<pre>" . $this->getNativeData() . "</pre>";

	// 	$output = $wgParser->parse($text, $title, $options, true, true, $revId);

	// }

}
