<?php

namespace Lve;

use \SpecialPage;

/**
 * inspired from RDFIO, samuel.lampa@gmail.com
 * @author maxime.lefrancois.86@gmail.com
 * @package VE
 */
class GetJsonLDContextPage extends SpecialPage {

	function __construct() {
		parent::__construct('GetJsonLDContext');
	}

	function getGroupName() {
		return 'lve';
	}

	// exemple: purl.org/NET/seas-model#Class
	// exemple: http://localhost/seas/wiki/index.php/Special:GetResource?vocabulary=rdfs&accept=html#Class
	function execute($par) {
		$output = $this->getOutput();
		$request = $this->getRequest();

		$name = $this->getRequest()->getText("name");

		// echo getallheaders()['Accept'];
		//		echo split(',', getallheaders()['Accept']);

		$title = \Title::newFromText("JsonLDContext:" . $name);
		if ($title !== null) {
			$article = new \Article($title);
			$content = $article->getContent();
			if ($content !== null) {
				// $this->getOutput()->disable();
				// wfResetOutputBuffers();
				$request->response()->header("Content-type: application/json; charset=utf-8");
				echo $content;
				exit;
			}
		}
		$output->addWikitext("This is a redirection page. Use URL parameter 'name' ''c'' to get the content of page [[JsonLDContext:c]] as JSON.");

	}

}