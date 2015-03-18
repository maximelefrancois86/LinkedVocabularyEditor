<?php

namespace Lve;

use EasyRdf\RdfNamespace;
use \Html;
use \SpecialPage;

/**
 * SpecialVEAdmin is a Special page for setting up the database tables for an ARC2 RDF Store
 * inspired from RDFIO, samuel.lampa@gmail.com
 * @author maxime.lefrancois.86@gmail.com
 * @package VE
 */
class NamespacesPage extends SpecialPage {

	function __construct() {
		parent::__construct('EditNamespaces');
	}

	function getGroupName() {
		return 'lve';
	}

	function execute($par) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$user = $this->getUser();
		$this->setHeaders();
		global $wgServer, $wgScriptPath;

		$newshort = $request->getText('short');
		$newlong = $request->getText('long');

		if ($newshort !== "" && $newlong !== "") {
			$this->checkRights();
			$output->addHTML("Added namespace " . $newshort . " with long version " . $newlong);
			RdfNamespace::set($newshort, $newlong);
			Arc2Store::writeNamespaces();
		}

		$namespaces = RdfNamespace::namespaces();
		$out = '<form id="namespaces-table" method="post" action="' . $wgServer . $wgScriptPath . '/index.php/Special:EditNamespaces">';
		$out .= Html::Hidden('token', $user->getEditToken());
		$out .= '<table>';
		$out .= ' <tr>';
		$out .= '  <th>prefix</th>';
		$out .= '  <th>namespace</th>';
		$out .= ' </tr>';

		foreach ($namespaces as $short => $long) {
			$out .= " <tr>";
			$out .= " <td>$short</td>";
			$out .= "  <td>$long</td>";
			$out .= " </tr>";
		}
		$out .= " <tr>";
		$out .= "  <td><input type='text' size='15' required pattern='\w+(-\w+)*' placeholder='short' name='short'/></td>";
		$out .= "  <td><input type='text' size='60' required pattern='https?://.+' placeholder='http://...' name='long'/></td>";
		$out .= " </tr>";
		$out .= '</table>';
		$out .= '<button type="submit">Register new namespace</button>';
		$out .= '</form>';
		$output->addHTML($out);
	}

	function checkRights() {
		$request = $this->getRequest();
		$user = $this->getUser();
		if (!$user->matchEditToken($request->getText('token'))) {
			die('Cross-site request forgery detected. Aborting.');
		}
	}

	/*	function editNamespace( $store, $namespaces ) {
// change namespaces in easyrdf,
// change namespaces in database,
// move wikipages,
// change wikipage content,
}
 */
}
