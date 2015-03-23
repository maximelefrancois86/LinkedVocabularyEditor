<?php

namespace Lve;

use ML\JsonLD\Document;
use ML\JsonLD\Graph;
use ML\JsonLD\LanguageTaggedString;
use ML\JsonLD\RdfConstants;
use ML\JsonLD\TypedValue;
use \ARC2;
use \Article;
use \EasyRdf\RdfNamespace;
use \Exception;
use \Html;
use \SpecialPage;

/**
 * inspired from RDFIO, samuel.lampa@gmail.com
 * @author maxime.lefrancois.86@gmail.com
 * @package VE
 */
class ImportPage extends SpecialPage {

	function __construct() {
		parent::__construct('ImportVocabularies');
	}

	function getGroupName() {
		return 'lve';
	}

	function execute($par) {
		$output = $this->getOutput();
		$request = $this->getRequest();
		$this->setHeaders();
		$this->outputHTMLForm();

		$action = $request->getText('action');
		if ($action == "uri" || $action == 'data') {
			$this->checkRights();
			if ($action == "uri") {
				$easyGraph = new \EasyRdf\Graph($request->getText('uri'));
				$easyGraph->load($request->getText('uri'), 'guess');
			} else {
				$easyGraph = new \EasyRdf\Graph($request->getText('import-uri'));
				$easyGraph->parse($request->getText('import-data'), 'guess', $request->getText('import-uri'));
			}
			if ($easyGraph != null) {
				$output->addWikitext("== Results ==");
				$output->addWikitext("Importing graph " . $easyGraph->getUri() . " in the wiki.");
				$this->updateStore($easyGraph);
				$this->updateWiki($easyGraph);
				Arc2Store::writeNamespaces();
				return;
			}
		} else if ($action == "export") {
			$export_uri = $request->getText('export-uri');
			$this->exportGraph();
		}
	}

	function checkRights() {
		$request = $this->getRequest();
		$user = $this->getUser();
		if (!$user->matchEditToken($request->getText('token'))) {
			die('Cross-site request forgery detected. Aborting.');
		}
	}

	function updateStore($easyGraph) {
		$store = Arc2Store::getStore();
		$guri = $easyGraph->getUri();
		$triples = Arc2Store::toTriples($easyGraph);
		$store->delete(1, $guri);
//		$store->insert($triples, $guri);
	}

	function updateWiki($easyGraph) {
		$output = $this->getOutput();
		$guri = $easyGraph->getUri();
		$output->addWikitext("Updated pages:");

		$updates = array();

		// every graph $guri in the Document model of Resource pages must be deleted.
		$titles = TitleUtil::allResourceTitles();
		foreach ($titles as $title) {
			$article = new Article($title);
			$content = $article->getPage()->getContent();
			if (isset($content)) {
				$document = $content->getDocument();
				if ($document->containsGraph($guri)) {
					$document->removeGraph($guri);
					$updates[$title->getText()] = array($article, $document);
				}
			}
		}

		$output->addInlineScript("console.log('start adding');");
		$resources = $easyGraph->resources();
		foreach ($resources as $uri => $s) {
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
				$updates[$title->getText()] = array($article, $document);
			}
		}

		foreach ($updates as $titletext => $update) {
			list($article, $document) = $update;
			$output->addInlineScript("console.log('" . $titletext . "'," . json_encode($document->toJsonLd()) . ");");
			$newContent = new JsonldContent($document->toJsonLd());
			$article->doEditContent($newContent, "updated graph $guri.");
			$output->addWikiText("* [[Resource:" . $titletext . "]]");
		}
	}

	function exportGraph() {
		$output = $this->getOutput();
		$request = $this->getRequest();
		$this->checkRights();
		$uri = $request->getText('export-uri');
		$format = $request->getText('export-format');

		$q = "CONSTRUCT { ?x ?p ?o } FROM <$uri> WHERE { ?x ?p ?o }";
		$store = Arc2Store::getStore();
		$rs = $store->query($q);

		$conf = array('ns' => RdfNamespace::namespaces());

		$this->getOutput()->disable();
		// Cancel output buffering and gzipping if set
		// This should provide safer streaming for pages with history
		wfResetOutputBuffers();

		$ser = null;

		if ($format == "RDF/XML") {
			$ser = ARC2::getRDFXMLSerializer($conf);
			$request->response()->header("Content-type: application/rdf+xml; charset=utf-8");
		} else {
			$ser = ARC2::getTurtleSerializer($conf);
			$request->response()->header("Content-type: text/turtle; charset=utf-8");
		}

		$filename = urlencode("export" . RdfNamespace::splitUri($uri)[0] . '.rdf');
		$request->response()->header("Content-disposition: attachment;filename={$filename}");

		echo $ser->getSerializedIndex($rs['result']);
		exit;
	}

	function toJsonLdObject($o, $g = null) {
		$output = $this->getOutput();
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

	function outputHTMLForm() {
		$output = $this->getOutput();
		$user = $this->getUser();
		global $wgServer, $wgScriptPath;

		$output->addHtml('<div><form method="post" action="' . $wgServer . $wgScriptPath . '/index.php/Special:ImportVocabularies">');
		$output->addHtml(Html::Hidden('token', $user->getEditToken()));

		// import form
		$output->addWikitext("== Import Linked Vocabulary ==");
		$output->addHtml('<fieldset><legend>From URI</legend>'
			. '<input type="url" size=80 pattern="https?://.+" name="uri"/>'
			. '<button type="submit" name="action" value="uri">Import</button>'
			. '</fieldset>');
		$output->addHtml('<fieldset><legend>From text</legend>'
			. '<textarea cols=80 rows=9 name="import-data"></textarea>'
			. '<input type="url" size=80 pattern="https?://.+" name="import-uri"/>'
			. '<button type="submit" name="action" value="data">Import</button>'
			. '</fieldset>');

		// export form
		$output->addWikitext("== Export == ");
		$output->addHtml('<fieldset><legend>From URI</legend>'
			. '<input type="url" size=100 pattern="https?://.+" name="export-uri"/>'
			. '<select name="export-format" value="turtle"><option value="turtle">turtle</option><option value="RDF/XML">RDF/XML</option></select>'
			. '<button type="submit" name="action" value="export">Export</button>'
			. '</fieldset>');

		$output->addHtml('</form></div>');

	}

}