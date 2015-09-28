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
use Lve\TitleUtil;
use \Article;
use \EasyRdf\RdfNamespace;
use \Exception;

class Reset extends Maintenance {
	public function execute() {
		global $wgARC2StoreConfig;
		try {
			set_time_limit(0);
			$store = ARC2::getStore($wgARC2StoreConfig);

			if ($store->isSetUp()) {
				// delete all pages
				$titles = TitleUtil::allResourceTitles();
				foreach ($titles as $title) {
					$article = new Article($title, 0);
					$ok = $article->doDeleteArticle("Database reset.");
				}
				$store->reset();
			} else {
				// set up the tables for ARC2
				$store->setUp();
			}
			if ($store->isSetUp()) {
				RdfNamespace::resetNamespaces();
				$toadd = array(
					"tzont" => "http://www.w3.org/2006/timezone#",
					"vann" => "http://purl.org/vocab/vann/",
					"rdf11" => "http://www.w3.org/TR/rdf11-concepts/#",
					"rdf-plain" => "http://www.w3.org/TR/rdf-plain-literal/",
					"ex" => "http://example.org/",
					"gr-vf" => "http://purl.org/goodrelations/",
					"schema" => "http://schema.org/",
					"ex" => "http://example.org/",
					"cc3" => "http://creativecommons.org/licenses/by/3.0/",
					"voaf" => "http://purl.org/vocommons/voaf#",
					"qudt-1.1" => "http://qudt.org/1.1/schema/qudt#",
					"vs" => "http://www.w3.org/2003/06/sw-vocab-status/ns#",
					"time" => "http://www.w3.org/2006/time#",
					"max" => "http://maxime-lefrancois.info/me#",
					"cim" => "http://vs.cs.hs-rm.de/cimowl.owl#",
					"saref" => "http://ontology.tno.nl/saref#",
					"om" => "http://www.wurvoc.org/vocabularies/om-1.8/",
					"onem2m" => "http://www.semanticweb.org/swetina/ontologies/2015/2/oneM2M_Base_Ontology#",
					"ifc4" => "http://www.buildingsmart-tech.org/ifcOWL/IFC4#",
					"qudt" => "http://qudt.org/schema/qudt#",
					"quantity" => "http://qudt.org/schema/quantity#",
					"qudt-quantity" => "http://qudt.org/vocab/quantity#",
					"prov-o" => "http://www.w3.org/TR/prov-o#",
					"prov-dictionary" => "http://www.w3.org/TR/prov-dictionary/",
					"prov-links" => "http://www.w3.org/TR/prov-links/",
					"qudt-unit" => "http://qudt.org/vocab/unit#",
					"dimension" => "http://qudt.org/schema/dimension#",
					"qudt-dimension" => "http://qudt.org/vocab/dimension#",
					"om" => "http://www.wurvoc.org/vocabularies/om-1.8/",
					"seas" => "http://purl.org/NET/seas#",
					"seas-time" => "http://purl.org/NET/seas/time#",
					"seas-eval" => "http://purl.org/NET/seas/eval#",
					"seas-qty" => "http://purl.org/NET/seas/quantities#",
					"seas-unit" => "http://purl.org/NET/seas/units#",
					"seas-actor" => "http://purl.org/NET/seas/actor#",
					"seas-weather" => "http://purl.org/NET/seas/weather#",
					"seas-ev" => "http://purl.org/NET/seas/ev#",
					"seas-pv" => "http://purl.org/NET/seas/pv#",
					"seas-energy" => "http://purl.org/NET/seas/energy#",
					"seas-building" => "http://purl.org/NET/seas/building#",
					"seas-spatial" => "http://purl.org/NET/seas/spatial#");
				foreach ($toadd as $prefix => $long) {
					RdfNamespace::set($prefix, $long);
				}
				Arc2Store::writeNamespaces();
				Arc2Store::getStore()->setSetting("graphs", array());
			} else {
				throw new Exception("setup failed", 0, $store->getErrors());
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

$maintClass = 'Reset';

require_once RUN_MAINTENANCE_IF_MAIN;