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
use \Exception;

class ListTitles extends Maintenance {
	public function execute() {
		try {
			echo "Listing titles\n";
			set_time_limit(0);

			if ($this->hasOption('uri')) {
				if ($this->hasOption('delete')) {
					$uris = Arc2Store::getStore()->setSetting($this->getOption('uri'), array());
				} else {
					$uris = Arc2Store::getStore()->getSetting($this->getOption('uri'), array());
					foreach ($uris as $uri) {
						echo $uri . "\n";
					}
				}
			} else {
				echo ("need option uri");

			}

		} catch (Exception $e) {
			echo 'Exception reçue : ', $e->getMessage(), "\n";
			echo $e->getCode(), "\n";
			echo $e->getFile(), "\n";
			echo $e->getLine(), "\n";
			echo $e->getTraceAsString(), "\n";
		}

	}

}

$maintClass = 'ListTitles';

require_once RUN_MAINTENANCE_IF_MAIN;