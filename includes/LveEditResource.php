<?php

namespace Lve;

use EasyRdf\RdfNamespace;
use ML\JsonLD\Document;
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

			$wgOut->setPageTitle("Editing " . $editor->mAbout->getPName()); // to be placed after
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
		$t = lcfirst($title->getText());

		if (!preg_match('#^\w+(-\w+)*:(\w+(-\w+)*)?$#', $t)) {
			return "Warning, the title of this page does not seems to be a correct prefixed name for a resource. Please use names such as 'Resource:prefix:fragment'";
		}
		list($prefix, $fragment) = explode(":", $t, 2);

		$guri = RdfNamespace::get($prefix);
		if (!$guri || $guri == "") {
			return "Warning, the prefix part does not seem to exist yet. Please register it at [[Special:Namespaces]]";
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
			$node->addPropertyValue(self::expand("rdfs", "label"), new LanguageTaggedString("", "en"));
			$node->addPropertyValue(self::expand("rdfs", "comment"), new LanguageTaggedString("", "en"));
			$node->addPropertyValue(self::expand("rdfs", "idDefinedBy"), $graph->createNode($guri));
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
		// $title = $article->getPage()->getTitle();
		// if ($title->getNamespace() != NS_RESOURCE) {
		// 	return true;
		// }

		// // get content,
		// $text = $content->getDocument();

		// global $wgOut;

		// $wgOut->addInlineScript("console.log('this revision', ".json_encode().");");
		// $wgOut->addInlineScript("console.log()");

		return true;
	}
}