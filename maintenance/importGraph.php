<?php
/**
 * To the extent possible under law,  I, Mark Hershberger, have waived all copyright and
 * related or neighboring rights to Hello World. This work is published from the
 * United States.
 *
 * @copyright CC0 http://creativecommons.org/publicdomain/zero/1.0/
 * @author Mark A. Hershberger <mah@everybody.org>
 * @ingroup Maintenance
 */

require_once __DIR__ . "/../../../maintenance/Maintenance.php";
//require_once __DIR__ . "/../LinkedVocabularyEditor.php";
require '../vendor/autoload.php';
require '../../../includes/content/Content.php';
require '../../../includes/content/AbstractContent.php';
require '../../../includes/content/TextContent.php';
require '../../../includes/content/JsonContent.php';
require '../includes/LveArc2Store.php';
require '../includes/LveTitleUtil.php';
require '../includes/LveResource.php';
require '../includes/LveJsonldContent.php';

use Lve\Arc2Store;
use Lve\JsonldContent;
use Lve\Resource;
use ML\JsonLD\Document;
use ML\JsonLD\Graph;
use ML\JsonLD\LanguageTaggedString;
use ML\JsonLD\RdfConstants;
use ML\JsonLD\TypedValue;
use \Article;
use \EasyRdf\RdfNamespace;
use \Exception;

class ImportGraph extends Maintenance {
	public function execute() {
		try {
			if (!$this->hasOption('filename') || !$this->hasOption('uri')) {
				echo "Need options 'uri' (the graph URI) and 'filename' (the file that contains a serialization of the Graph)";
				return;
			}
			$guri = $this->getOption('uri');
			$filename = $this->getOption('filename');

			echo "Setting graph <" . $guri . "> with content of " . $filename . "\n";
			set_time_limit(0);

			// update graphs setting
			$guris = Arc2Store::getStore()->getSetting("graphs");
			if ($guris == null) {
				$guris = array();
			}
			$flag = false;
			foreach ($guris as $g) {
				if ($g == $guri) {
					$flag = true;
				}
			}
			if (!$flag) {
				array_push($guris, $guri);
				Arc2Store::getStore()->setSetting("graphs", $guris);
			}

			$easyGraph = $this->getEasyGraph($guri, $filename);

			$this->updateStore($guri, $easyGraph);

			$this->updateWiki($guri, $easyGraph);

			Arc2Store::writeNamespaces();

		} catch (Exception $e) {
			echo 'Exception reçue : ', $e->getMessage(), "\n";
			echo $e->getCode(), "\n";
			echo $e->getFile(), "\n";
			echo $e->getLine(), "\n";
			echo $e->getTraceAsString(), "\n";
		}

	}

	public function getEasyGraph($guri, $filename) {
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		$easyGraph = new \EasyRdf\Graph($guri);
		$easyGraph->parse($contents, 'guess', $guri);
		if ($easyGraph->getUri() != $guri) {
			throw new Exception("graph URI and base should be the same!");
		}
		if ($easyGraph == null) {
			throw new Exception("easyGraph is null");
		}
		return $easyGraph;
	}

	public function updateStore($guri, $easyGraph) {
		$store = Arc2Store::getStore();
		$store->delete(0, $guri);
		$triples = Arc2Store::toTriples($easyGraph);
		$store->insert($triples, $guri);
	}

	public function updateWiki($guri, $easyGraph) {
		$updates = array();

		// delete graph from articles that described that graph
		$uris = Arc2Store::getStore()->getSetting($guri);
		if ($uris == null) {
			echo "no wiki page to delete\n";
		} else {
			foreach ($uris as $uri) {
				echo "Old resource <" . $uri . ">\n";
				$title = Resource::get($uri)->getTitle();
				$article = new Article($title);
				$content = $article->getPage()->getContent();
				if (!isset($content)) {
					throw new Exception("title <" . $uri . "> was expected to have content");

				}
				$document = $content->getDocument();
				if (!$document->containsGraph($guri)) {
					throw new Exception("title <" . $uri . "> was expected to contain graph <" . $guri . ">");
				}
				$document->removeGraph($guri);
				$updates[$title->getText()] = array($article, $document);
			}
		}

		// add graph to articles
		$uris = array();
		$resources = $easyGraph->resources();
		foreach ($resources as $uri => $s) {
			try {
				echo "New resource <" . $uri . ">\n";
				$title = Resource::get($uri)->getTitle();
				if (isset($updates[$title->getText()])) {
					list($article, $document) = $updates[$title->getText()];
				} else {
					$article = new Article($title);
					$content = $article->getPage()->getContent();
					$document = isset($content) ? $content->getDocument() : new Document();
				}
				$g = $document->createGraph($guri);
				$n = $g->createNode($uri);
				$flag = false;
				foreach ($s->properties() as $p) {
					foreach ($s->all($p) as $o) {
						$flag = true;
						$n->addPropertyValue(RdfNamespace::expand($p), $this->toJsonLdObject($o, $g));
					}
				}
				// foreach ($s->all("^rdfs:domain") as $o) {
				// 	$this->toJsonLdObject($o, $g)->addPropertyValue(RdfNamespace::expand("rdfs:domain"), $n);
				// }
				// foreach ($s->all("^rdfs:range") as $o) {
				// 	$this->toJsonLdObject($o, $g)->addPropertyValue(RdfNamespace::expand("rdfs:range"), $n);
				// }
				if ($flag) {
					array_push($uris, $uri);
					$updates[$title->getText()] = array($article, $document);
				}
			} catch (Exception $ex) {
			}
		}

		// execute updates
		foreach ($updates as $titletext => $update) {
			list($article, $document) = $update;
			if (count($document->getGraphNames()) <= 1 && strrpos($document->getGraphNames()[0], "_:") !== FALSE) {
				echo "Deleting " . $titletext . "\n";
				$article->doDeleteArticle("No more graph after importing graph <$guri>.");
			} else {
				echo "Updating " . $titletext . "\n";
				$newContent = new JsonldContent($document->toJsonLd());
				$article->doEditContent($newContent, "Content changed after importing graph <$guri>.");
			}
		}

		Arc2Store::getStore()->setSetting($guri, $uris);

	}

	function toJsonLdObject($o, $g = null) {
		if ($o instanceof \EasyRdf\Resource) {
			if (!($g instanceof Graph)) {
				throw new Exception("invalid argument");
			}
			return $g->createNode($o->getUri());
		}
		if ($o instanceof \EasyRdf\Literal) {
			if ($o->getLang() !== null) {
				return new LanguageTaggedString($o->getValue(), $o->getLang());
			}
			if ($o->getDatatypeUri() !== null) {
				return new TypedValue($o->__toString(), $o->getDatatypeUri());
			}
			return new TypedValue($o->getValue(), RdfConstants::XSD_STRING);
		}
		throw new Exception("invalid argument");
	}

}

$maintClass = 'ImportGraph';

require_once RUN_MAINTENANCE_IF_MAIN;