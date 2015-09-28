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
use \Article;
use \Exception;

class DeleteGraph extends Maintenance {
	public function execute() {
		try {
			if (!$this->hasOption('uri')) {
				echo "Need options 'uri' (the graph URI)";
				return;
			}
			$guri = $this->getOption('uri');

			echo "Deleting graph <" . $guri . ">\n";
			set_time_limit(0);

			Arc2Store::getStore()->delete(1, $guri);
			$this->updateStore($guri);

			$guris = Arc2Store::getStore()->getSetting("graphs");
			foreach ($guris as $i => $g) {
				if ($g == $guri) {
					unset($guris[$i]);
				}
			}
			Arc2Store::getStore()->setSetting("graphs", $guris);
		} catch (Exception $e) {
			echo 'Exception reçue : ', $e->getMessage(), "\n";
			echo $e->getCode(), "\n";
			echo $e->getFile(), "\n";
			echo $e->getLine(), "\n";
			echo $e->getTraceAsString(), "\n";
		}

	}

	public function updateStore($guri) {
		// delete graph from articles that described that graph
		$uris = Arc2Store::getStore()->getSetting($guri);
		if ($uris == null) {
			echo "no wiki page to delete\n";
		} else {
			foreach ($uris as $uri) {
				$title = Resource::get($uri)->getTitle();
				$article = new Article($title);
				$content = $article->getPage()->getContent();
				if (!isset($content)) {
					throw new Exception("title <$uri> was expected to have content");

				}
				$document = $content->getDocument();
				if (!$document->containsGraph($guri)) {
					throw new Exception("title <$uri> was expected to contain graph <" . $guri . ">");
				}
				$document->removeGraph($guri);

				if (count($document->getGraphNames()) <= 1 && strrpos($document->getGraphNames()[0], "_:") !== FALSE) {
					echo "Deleting <$uri>\n";
					$article->doDeleteArticle("No more graph after deleting graph <$guri>.");
				} else {
					echo "Updating <$uri>\n";
					$newContent = new JsonldContent($document->toJsonLd());
					$article->doEditContent($newContent, "Content changed after deleting graph <$guri>.");
				}
			}
		}
		$uris = Arc2Store::getStore()->setSetting($guri, null);

	}
}

$maintClass = 'DeleteGraph';

require_once RUN_MAINTENANCE_IF_MAIN;