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

use EasyRdf\RdfNamespace;
use Lve\Arc2Store;
use \Exception;

class Namespaces extends Maintenance {
	public function execute() {
		try {
			echo ("options delete, add and ns");

			$guris = Arc2Store::getStore()->getSetting("ns");

			var_dump($guris);

			if ($this->hasOption('pre') && $this->hasOption('add')) {
				throw new Exception("Only one option of {pre, add} can be used.");
			}

			if ($this->hasOption('pre')) {
				echo ("deleting namespace " . $this->getOption('pre'));
				RdfNamespace::delete($this->getOption('pre'));
				Arc2Store::writeNamespaces($guris);
			}

			if ($this->hasOption('add')) {
				if (!$this->hasOption('ns')) {
					throw new Exception("need also option ns.");
				}
				echo ("setting namespace " . $this->getOption('add') . " to " . $this->getOption('ns'));
				RdfNamespace::set($this->getOption('add'), $this->getOption('ns'));
				Arc2Store::writeNamespaces($guris);
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

$maintClass = 'Namespaces';

require_once RUN_MAINTENANCE_IF_MAIN;