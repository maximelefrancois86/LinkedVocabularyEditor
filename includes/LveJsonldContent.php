<?php

namespace Lve;

use EasyRdf\RdfNamespace;
use ML\JsonLD\Document;
use ML\JsonLD\JsonLD;
use \HTML;
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

		global $wgOut;
		$wgOut->addHTML("
			<!--
			    IE8 support, see AngularJS Internet Explorer Compatibility http://docs.angularjs.org/guide/ie
			    For Firefox 3.6, you will also need to include jQuery and ECMAScript 5 shim
			  -->
			<!--[if lt IE 9]>
		    <script src='http://cdnjs.cloudflare.com/ajax/libs/es5-shim/2.2.0/es5-shim.js'></script>
		  <![endif]-->");

		// Resource creation form
		$wgOut->addinlineScript('
			var namespaces = ' . json_encode(RdfNamespace::namespaces()) . ';
			var wikiurl = ' . json_encode(Resource::getByTitle($title)->getUri()) . ';
			$( document ).ready(function() {
				$("#catlinks").next().after( "<div class=\'catlinks\' id=\'create\'><label>Create new resource:</label><select/><input size=12/> <a></a></div>");
				var input = $("#create input");
				var select = $("#create select");
				var a = $("#create a");

				var prefixes = [];
				for (var prefix in namespaces) {
				  if (namespaces.hasOwnProperty(prefix)) {
				  	prefixes.push(prefix);
				  }
				}
				prefixes.sort();
				for(var i in prefixes) {
					var prefix = prefixes[i] ;
					var Prefix = prefix.charAt(0).toUpperCase() + prefix.slice(1) ;
					if(prefix=="Seas") {
				    	select.append("<option value=\'"+Prefix+"\' selected>"+prefix+"</option>");
					} else {
				    	select.append("<option value=\'"+Prefix+"\'>"+prefix+"</option>");
					}
				}
				var updateFunc = function() {
					var nsprefix = select.val();
					var value = input.val();
					a.attr("href","../index.php?title=Resource:"+nsprefix+":"+value+"&action=edit");
					a.text("Create wiki page Resource:"+nsprefix+":"+value);
				};
				$("#create input").keyup(updateFunc);
				$("#create select").change(updateFunc);
				updateFunc();
			});
		');

		$text = "__NOTOC__ __NOEDITSECTION__\n";

		$wgOut->addinlineScript('
			$( document ).ready(function() {
				$(".ontoview").first().before( "<div  class=\'catlinks\'><label for=\'view\'>View definition in graph:</label><select id=\'view\'/></div>\n");
				$(".ontoview").each(function(index){
					$("#view").append("<option value=\'"+$( this ).attr("id")+"\'>"+$( this ).attr("id")+"</option>");
				});
				chooseOnto = function() {
					$(".ontoview").each(function(index){
						if($(this).attr("id")==$("#view").val()) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
				}
				$("#view").change(chooseOnto);
				chooseOnto();
			});
		');
		foreach ($this->getDocument()->getGraphNames() as $guri) {
			if (strpos($guri, "_:") === 0) {
				continue;
			}
			$onto = Resource::get($guri)->getPName();
			$gwuri = Resource::get($guri)->getTitle()->getFullUrl();

			$uri = $this->about->getUri();
			$pname = $this->about->getPName();
			$wuri = $title->getFullUrl();
			$urlwiki = substr($wuri, 0, strrpos($wuri, ":") + 1);
			$gurlwiki = substr($gwuri, 0, strrpos($gwuri, ":") + 1);

			$localName = substr($wuri, strrpos($wuri, ":") + 1);
			$sep = ((strpos($wuri, "?title=") !== false)) ? "&" : "?";
			$gurlrdf = $gurlwiki . $sep . 'accept=application/rdf%2bxml';
			$gurlttl = $gurlwiki . $sep . 'accept=text/turtle#';
			$gurln3 = $gurlwiki . $sep . 'accept=application/n-triples#';

			$text .= "<div class='ontoview' id='$onto'>\n";
			$text .= "===Description of $pname in graph $onto===\n\n";
			$text .= "* Resource IRI: $uri\n";
			$text .= "* Graph IRI: $guri\n";
			$text .= "* Graph [[Resource:$onto|wiki page]]\n";
			$text .= "* Graph Sources: ([$gurlrdf application/rdf+xml], [$gurlttl application/turtle], [$gurln3 application/n-triples])\n";
			if (strlen($wuri) === strrpos($wuri, ":") + 1) {
				$this->fillAsOntology($guri, $onto, $text);
			} else {
				$this->fillAsResource($guri, $onto, $text);
			}
			$text .= "</div>";
		}

		$output = $wgParser->parse($text, $title, $options, true, true, $revId);
	}

	protected function fillAsOntology($guri, $onto, &$text) {
		$uri = $this->about->getUri();

		$text .= "[[Category:Ontology]]\n\n";
		$text .= "===List of resources===\n\n";
		$text .= "* [[:Category:Resources in $onto|Resources]]\n";
		$text .= "* [[:Category:Classes in $onto|Classes]]\n";
		$text .= "* [[:Category:Properties in $onto|Properties]]\n";
		$text .= "* [[:Category:OWL Classes in $onto|OWL Classes]]\n";
		$text .= "* [[:Category:OWL Object Properties in $onto|OWL Object Properties]]\n";
		$text .= "* [[:Category:OWL Data Properties in $onto|OWL Data Properties]]\n";

		$this->fillValues($guri, $onto, $text, "Title", "http://purl.org/dc/terms/title");
		$this->fillValues($guri, $onto, $text, "Abstract", "http://purl.org/dc/terms/description");
		$this->fillValues($guri, $onto, $text, "Version Info", "http://www.w3.org/2002/07/owl#versionInfo");
		$this->fillValues($guri, $onto, $text, "Comment", "http://www.w3.org/2000/01/rdf-schema#comment");
		$this->fillResources($guri, $onto, $text, "License", "http://creativecommons.org/ns#license");
		$this->fillResourcesType($guri, $onto, $text, "Ontology Classes", "http://www.w3.org/2002/07/owl#Class");
		$this->fillResourcesType($guri, $onto, $text, "Ontology Object Properties", "http://www.w3.org/2002/07/owl#ObjectProperty");
		$this->fillResourcesType($guri, $onto, $text, "Ontology Data Properties", "http://www.w3.org/2002/07/owl#DataProperty");
		$this->fillResourcesType($guri, $onto, $text, "Other Classes", "http://www.w3.org/2000/01/rdf-schema#Class");
		$this->fillResourcesType($guri, $onto, $text, "Other Properties", "http://www.w3.org/1999/02/22-rdf-syntax-ns#Property");

	}

	protected function fillResourcesType($guri, $onto, &$text, $type, $turi) {
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

	protected function fillResources($guri, $onto, &$text, $title, $puri) {
		$uri = $this->about->getUri();
		$pname = $this->about->getPName();
		$q = "SELECT DISTINCT ?o FROM <$guri> WHERE {
			<$uri> <$puri> ?o .
		} ORDER BY ?o";
		$rs = \Lve\Arc2Store::getStore()->query($q);

		if (count($rs['result']['rows']) >= 1) {
			$text .= "===$title===\n\n";
		}
		foreach ($rs['result']['rows'] as $row) {
			$res = Resource::get($row['o']);
			if ($puri == "http://www.w3.org/1999/02/22-rdf-syntax-ns#type") {
				if ($res->getPName() == "rdfs:Class") {
					$text .= "[[Category:Classes in $onto]]\n\n";
				}
				if ($res->getPName() == "rdf:Property") {
					$text .= "[[Category:Properties in $onto]]\n\n";
				}
				if ($res->getPName() == "rdfs:Class") {
					$text .= "[[Category:OWL Classes in $onto]]\n\n";
				}
				if ($res->getPName() == "owl:ObjectProperty") {
					$text .= "[[Category:OWL Object Properties in $onto]]\n\n";
				}
				if ($res->getPName() == "owl:DatatypeProperty") {
					$text .= "[[Category:OWL Data Properties in $onto]]\n\n";
				}
			}
			$this->printResource($guri, $onto, $text, $res);
		}
	}

	protected function fillResourcesReverse($guri, $onto, &$text, $title, $puri) {
		$uri = $this->about->getUri();
		$pname = $this->about->getPName();
		$q = "SELECT DISTINCT ?o FROM <$guri> WHERE {
			?o <$puri> <$uri> .
		} ORDER BY ?o";
		$rs = \Lve\Arc2Store::getStore()->query($q);

		if (count($rs['result']['rows']) >= 1) {
			$text .= "===$title===\n\n";
		}
		foreach ($rs['result']['rows'] as $row) {
			$this->printResource($guri, $onto, $text, Resource::get($row['o']));
		}
	}

	protected function printResource($guri, $onto, &$text, $r) {
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

	protected function fillAsResource($guri, $onto, &$text) {
		$text .= "[[Category:Resources]]\n";
		$text .= "[[Category:Resources in $onto]]\n";

		$this->fillValues($guri, $onto, $text, "Human-readable names", "http://www.w3.org/2000/01/rdf-schema#label");
		$this->fillResources($guri, $onto, $text, "Types", "http://www.w3.org/1999/02/22-rdf-syntax-ns#type");
		$this->fillValues($guri, $onto, $text, "Human-readable descriptions", "http://www.w3.org/2000/01/rdf-schema#comment");
		$this->fillValues($guri, $onto, $text, "Term status", "http://www.w3.org/2003/06/sw-vocab-status/ns#term_status");
		$this->fillValues($guri, $onto, $text, "Additional informations", "http://www.w3.org/2003/06/sw-vocab-status/ns#moreinfos");

		$this->fillResources($guri, $onto, $text, "Direct super classes", "http://www.w3.org/2000/01/rdf-schema#subClassOf");
		$this->fillResourcesReverse($guri, $onto, $text, "Direct sub classes", "http://www.w3.org/2000/01/rdf-schema#subClassOf");
		$this->fillResources($guri, $onto, $text, "Equivalent classes", "http://www.w3.org/2002/07/owl#equivalentClass");
		$this->fillResources($guri, $onto, $text, "Disjoint classes", "http://www.w3.org/2002/07/owl#disjointWith");
		$this->fillResourcesReverse($guri, $onto, $text, "Domain Of", "http://www.w3.org/2000/01/rdf-schema#domain");
		$this->fillResourcesReverse($guri, $onto, $text, "Range Of", "http://www.w3.org/2000/01/rdf-schema#range");

		$this->fillResources($guri, $onto, $text, "Direct super properties", "http://www.w3.org/2000/01/rdf-schema#subPropertyOf");
		$this->fillResourcesReverse($guri, $onto, $text, "Direct sub properties", "http://www.w3.org/2000/01/rdf-schema#subPropertyOf");
		$this->fillResources($guri, $onto, $text, "Known domains", "http://www.w3.org/2000/01/rdf-schema#domain");
		$this->fillResources($guri, $onto, $text, "Known ranges", "http://www.w3.org/2000/01/rdf-schema#range");
		$this->fillResources($guri, $onto, $text, "Known equivalent properties", "http://www.w3.org/2002/07/owl#equivalentProperty");
		$this->fillResources($guri, $onto, $text, "Known disjoint properties", "http://www.w3.org/2002/07/owl#propertyDisjointWith");
		$this->fillResources($guri, $onto, $text, "Known inverse properties", "http://www.w3.org/2002/07/owl#inverseOf");

	}

	protected function fillValues($guri, $onto, &$text, $title, $puri) {
		$uri = $this->about->getUri();
		$pname = $this->about->getPName();
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
