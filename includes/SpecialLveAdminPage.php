<?php

namespace Lve;

use EasyRdf\RdfNamespace;
use \ARC2;
use \Article;
use \Html;
use \SpecialPage;

/**
 * SpecialVEAdmin is a Special page for setting up the database tables for an ARC2 RDF Store
 * inspired from RDFIO, samuel.lampa@gmail.com
 * @author maxime.lefrancois.86@gmail.com
 * @package VE
 */
class AdminPage extends SpecialPage {

	function __construct() {
		// Do not display your SpecialPage on the Special:SpecialPages page add 'editinterface'
		//		parent::__construct( 'OkOk', 'editinterface' ); // real name is 'okok'. see alias.
		parent::__construct('AdminPage', 'editinterface');
	}

	/**
	 * Override the parent to set where the special page appears on Special:SpecialPages
	 * 'other' is the default, so you do not need to override if that's what you want.
	 * Specify 'media' to use the <code>specialpages-group-media</code> system interface
	 * message, which translates to 'Media reports and uploads' in English;
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'lve';
	}

	function execute($par) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$user = $this->getUser();
		$this->setHeaders();

		// Do not allow url access to your SpecialPage
		if (!$this->userCanExecute($user)) {
			$this->displayRestrictionError();
			return;
		};

		global $wgARC2StoreConfig, $wgServer, $wgScriptPath;

		$store = ARC2::getStore($wgARC2StoreConfig);

		$action = $request->getText('action');
		if ($action == "setup") {
			$this->checkRights();
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
				$output->addWikiText(wfMessage('lve-setup-fresh')->text());
				RdfNamespace::resetNamespaces();
				RdfNamespace::set("vann", "http://purl.org/vocab/vann/");
				RdfNamespace::set("voaf", "http://purl.org/vocommons/voaf#");
				RdfNamespace::set("seas", "http://purl.org/NET/seas#");
				RdfNamespace::set("vs", "http://www.w3.org/2003/06/sw-vocab-status/ns#");
				Arc2Store::writeNamespaces();
			} else {
				$this->getOutput()->addWikiText(wfMessage('lve-setup-failed')->text());
				$msg = "";
				foreach ($store->getErrors() as $error) {
					$msg .= "<li>" . $error . "</li>";
				}
				$output->addHTML("<ul>" . $msg . "</ul>");
			}
		} else {
			if ($store->isSetUp()) {
				$this->getOutput()->addWikiText(wfMessage('lve-setup-set')->text());
			} else {
				$this->getOutput()->addWikiText(wfMessage('lve-setup-notset')->text());
			}
		}

		$htmlOutput = '<div><form method="get" action="' . $wgServer . $wgScriptPath . '/index.php/Special:AdminPage" name="createEditQuery">';
		$htmlOutput .= '<button type="submit" name="action" value="setup">' . wfMessage('lve-setup-button')->text() . '</button>';
		$htmlOutput .= Html::Hidden('token', $user->getEditToken());
		$htmlOutput .= '</form></div>';

		$output->addHTML($htmlOutput);
	}

	function checkRights() {
		$request = $this->getRequest();
		$user = $this->getUser();
		if (!$user->matchEditToken($request->getText('token'))) {
			die('Cross-site request forgery detected. Aborting.');
		}
		if (!in_array('sysop', $user->getEffectiveGroups())) {
			$this->displayRestrictionError();
			return;
		}
	}
}