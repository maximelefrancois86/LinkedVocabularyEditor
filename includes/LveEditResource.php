<?php

namespace Lve;

use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use ML\JsonLD\Document;
use ML\JsonLD\JsonLD;
use ML\JsonLD\LanguageTaggedString;
use ML\JsonLD\TypedValue;
use \Article;
use \EditPage;
use \HTML;
use \OutputPage;

class EditResource extends \EditPage {

	/** @var Resource */
	public $mAbout;

	public function __construct(Article $article) {
		parent::__construct($article);
		$this->mAbout = Resource::getByTitle($this->mTitle);
	}

	public static function onAlternateEdit(EditPage $editor) {
		global $wgOut;
		if ($editor->getArticle()->getPage()->getTitle()->getNamespace() == NS_RESOURCE) {
			// should define $wgResourceModules instead

			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/vendor/angular/angular.js"));
			$wgOut->addScript(Html::linkedStyle("extensions/LinkedVocabularyEditor/web/vendor/angular/angular-csp.css"));

			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/vendor/angular-ui-select/dist/select.js"));
			$wgOut->addScript(Html::linkedStyle("extensions/LinkedVocabularyEditor/web/vendor/angular-ui-select/dist/select.css"));
			$wgOut->addScript(Html::linkedStyle("http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.5/select2.css"));

			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/vendor/angular-sanitize/angular-sanitize.js"));

			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/vendor/ngDialog/js/ngDialog.js"));
			$wgOut->addScript(Html::linkedStyle("extensions/LinkedVocabularyEditor/web/vendor/ngDialog/css/ngDialog.css"));
			$wgOut->addScript(Html::linkedStyle("extensions/LinkedVocabularyEditor/web/vendor/ngDialog/css/ngDialog-theme-default.css"));

			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/vendor/es6-promise/promise.js"));
			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/vendor/jsonld/js/jsonld.js"));

			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/linked-resource-editor/linked-resource-editor.js"));
			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/directives.js"));
			$wgOut->addScript(Html::linkedScript("extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/directives.js"));
			$wgOut->addScript(Html::linkedStyle("extensions/LinkedVocabularyEditor/web/linked-resource-editor/linked-resource-editor.css"));

			$wgOut->addHTML(" <!--
				    IE8 support, see AngularJS Internet Explorer Compatibility http://docs.angularjs.org/guide/ie
				    For Firefox 3.6, you will also need to include jQuery and ECMAScript 5 shim
				  -->
				<!--[if lt IE 9]>
			    <script src='http://cdnjs.cloudflare.com/ajax/libs/es5-shim/2.2.0/es5-shim.js'></script>
			    <script>
			      document.createElement('ui-select');
			      document.createElement('ui-select-match');
			      document.createElement('ui-select-choices');
			    </script>
			  <![endif]-->");

			$wgOut->setPageTitle("Editing resource " . $editor->mAbout->getPName()); // to be placed after
			$wgOut->addWikitext("'''URI: '''= " . $editor->mAbout->getExtLink() . "\n");

			// bind JSON-LD document to a javascript variable
			$wgOut->addinlineScript('
				var about = "' . $editor->mAbout->getPName() . '";
				var namespaces = ' . json_encode(RdfNamespace::namespaces()) . ';
				var doc;
			');
			return true;
		}
	}

	public static function showEditFormFields(EditPage $editor, OutputPage $output) {
		global $wgOut;
		if ($editor->getArticle()->getTitle()->getNamespace() == NS_RESOURCE) {
			$wgOut->addHTML('<vre-root></vre-root>');

			// bind JSON-LD document to a javascript variable
			$wgOut->addinlineScript('
				// manually bootstrap module to element vEditor
				$.getJSON("extensions/LinkedVocabularyEditor/resources/languages.json", function(data) {
					languages = data;
				});
				$.getJSON("extensions/LinkedVocabularyEditor/resources/datatypes.json", function(data) {
					datatypes = data;
				});
				$.getJSON("extensions/LinkedVocabularyEditor/api/resources_info.php", function(data) {
					resources_info = data;
				});

				$(document).ready(function() {
					// $("#wpTextbox1").hide();
					$("#toolbar").hide();
					editForm = document.getElementById("editform").setAttribute("ng-controller", "VEController");
					jsonld.expand(JSON.parse($("#wpTextbox1").text()), function(err, expanded) {
						console.log("received jsonld", expanded);
						jsonld = expanded;
						i = 0;
						interval = setInterval(function(){
							i++;
							if (i>10) {
								clearInterval(interval);
							}
							console.log("timeout200");
							var doesAppInitialized = angular.element(document).scope();
							if (!angular.isUndefined(doesAppInitialized)) { //if it is not
								clearInterval(interval);
							} else {
								angular.bootstrap(document, ["veApp"]);
							}
						}, 200);

					});
				});

			');
		}
	}

	public static function onEditFormPreloadText(&$text, &$title) {
		if ($title->getNamespace() != NS_RESOURCE) {
			return true;
		}
		$mAbout = Resource::getByTitle($title);
		$t = $mAbout->getPName();

		if (!preg_match('#^\w+(-\w+)*:(\w+([-\_]\w+)*)?$#', $t)) {
			return "Warning, the title of this page does not seems to be a correct prefixed name for a resource. Please use names such as 'Resource:prefix:fragment'";
		}
		list($prefix, $fragment) = explode(":", $t, 2);

		$guri = RdfNamespace::get($prefix);
		if (!$guri || $guri == "") {
			return "Warning, prefix <strong>$prefix</strong> has not been registered yet. Please register it at <a href='index.php?title=Special:EditNamespaces'>Special:EditNamespaces</a>";
		}

		$doc = new Document();
		$graph = $doc->createGraph($guri);
		$node = $graph->createNode($guri . $fragment);
		if ($fragment == "") {
			$node->addPropertyValue(self::expand("rdf", "type"), $graph->createNode(self::expand("owl", "Ontology")));
			$node->addPropertyValue(self::expand("rdf", "type"), $graph->createNode(self::expand("vann", "Vocabulary")));
			$node->addPropertyValue(self::expand("dc", "title"), new LanguageTaggedString("", "en"));
			$node->addPropertyValue(self::expand("dc", "description"), new LanguageTaggedString("", "en"));
			$node->addPropertyValue(self::expand("rdfs", "comment"), new LanguageTaggedString("", "en"));
			$node->addPropertyValue(self::expand("vann", "preferredNamespacePrefix"), $prefix);
			$node->addPropertyValue(self::expand("vann", "preferredNamespaceUri"), $guri);
			$node->addPropertyValue(self::expand("dc", "issued"), new TypedValue(date("Y-m-d"), self::expand("xsd", "date")));
			$node->addPropertyValue(self::expand("dc", "modified"), new TypedValue(date("Y-m-d"), self::expand("xsd", "date")));
			$node->addPropertyValue(self::expand("owl", "versionInfo"), "v0.1");
			$node->addPropertyValue(self::expand("cc", "license"), "");
			$node->addPropertyValue(self::expand("dc", "creator"), "");
		} else {
			$node->addPropertyValue(self::expand("rdfs", "label"), new LanguageTaggedString($fragment, "en"));
			$node->addPropertyValue(self::expand("rdfs", "comment"), new LanguageTaggedString("", "en"));
			$node->addPropertyValue(self::expand("rdfs", "isDefinedBy"), $graph->createNode($guri));
			$node->addPropertyValue(self::expand("vs", "term_status"), "unstable");
		}

		$content = new JsonldContent($doc->toJsonLd());
		$text = $content->getNativeData();

		return true;
	}

	private static function expand($prefix, $fragment) {
		return RdfNamespace::expand("$prefix:$fragment");
	}

	public static function onPageContentSaveComplete($article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId) {
		$title = $article->getTitle();
		if ($title->getNamespace() != NS_RESOURCE) {
			return true;
		}

		// for every graph, generate the sparql update that modifies the named graph
		$store = Arc2Store::getStore();

		$text = "";

		if ($article->getRevision()->getPrevious() !== null) {
			$olddoc = $article->getRevision()->getPrevious()->getContent()->getDocument();
			foreach ($olddoc->getGraphNames() as $graph) {
				if (strrpos($graph, "_:") === 0) {
					continue;
				}
				$oldgraph = JsonLD::toString($olddoc->getGraph($graph)->toJsonLd());
				$oldEasyGraph = new \EasyRdf\Graph();
				$oldEasyGraph->parse($oldgraph, "jsonld");
				$oldN3 = $oldEasyGraph->serialise("ntriples");
				$text .= "\n\n DELETE FROM <$graph> { $oldN3 }";
				$store->query("DELETE FROM <$graph> { $oldN3 }");
			}
		}

		$newdoc = $article->getContent()->getDocument();
		foreach ($newdoc->getGraphNames() as $graph) {
			if (strrpos($graph, "_:") === 0) {
				continue;
			}
			$newgraph = JsonLD::toString($newdoc->getGraph($graph)->toJsonLd());
			$newEasyGraph = new \EasyRdf\Graph();
			$newEasyGraph->parse($newgraph, "jsonld");
			$newN3 = $newEasyGraph->serialise("ntriples");
			$text .= "\n\n INSERT INTO <$graph> { $newN3 }";
			$store->query("INSERT INTO <$graph> { $newN3 }");
		}

		// $content = new \WikitextContent($text);

		// $title = \Title::newFromText("Test:" . $article->getTitle()->getText());

		// $article = new Article($title);
		// $article->doEditContent($content, "updated graph.");

		return true;

	}
}