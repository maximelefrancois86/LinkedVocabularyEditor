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

class PrintAll extends Maintenance {
	public function execute() {
		try {
			echo "Print all graphs\n";
			set_time_limit(0);

			$guris = Arc2Store::getStore()->getSetting("graphs", array());
			foreach ($guris as $guri) {
				echo "\n############################################\n";
				echo "############################################\n";
				echo "# Graph $guri\n";
				echo "###############\n\n";

				$q = "CONSTRUCT { ?x ?p ?o } FROM <$guri> WHERE { ?x ?p ?o }";
				$store = Arc2Store::getStore();
				$rs = $store->query($q);

				$conf = array('ns' => \EasyRdf\RdfNamespace::namespaces());

				$ser = ARC2::getTurtleSerializer($conf);
				echo $ser->getSerializedIndex($rs['result']);
				echo "\n##############\n";
				echo "############################################\n\n";

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

$maintClass = 'PrintAll';

require_once RUN_MAINTENANCE_IF_MAIN;