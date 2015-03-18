<?php
/**
 * Initializing file for VE extension.
 *
 * @file
 * @ingroup VE
 */
if (!defined('MEDIAWIKI')) {
	echo ("This is an extension to the MediaWiki package and cannot be run standalone.\n");
	die(-1);
}

define('VERSION', '0.1');

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'namemsg' => 'lve',
	'author' => '[http://maxime-lefrancois.info Maxime Lefrançois]',
	'version' => VERSION,
	'url' => 'http://maxime-lefrancois.info/VE', //'https://www.mediawiki.org/wiki/Extension:VocabularyEditor',
	'descriptionmsg' => 'lve-desc',
);

// Shortcut to this extension directory
$dir = __DIR__ . '/';

/**************************
 *  Dependencies
 *  JsonLD, ARC2, EasyRdf
 **************************/

require 'vendor/autoload.php';

/**************************
 *  ARC2 RDF Store config  *
 **************************/

/* Customize this config if you   *
 * want to use an external database */
$wgARC2StoreConfig = array(
	'db_host' => $wgDBserver,
	'db_name' => $wgDBname,
	'db_user' => $wgDBuser,
	'db_pwd' => $wgDBpassword,
	'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix
);

/**************************
 *  Deferred setup of Namespaces  *
 **************************/
$wgExtensionFunctions[] = 'Lve\Arc2Store::readNamespaces';

/****************************
 * i18n
 ****************************/
$wgMessagesDirs['Lve'] = $dir . 'i18n';
$wgExtensionMessagesFiles['LveAlias'] = $dir . 'LinkedVocabularyEditor.alias.php'; # Location of an aliases file (Tell MediaWiki to load it)

/**************************
 *  New namespace            *
 **************************/
define("NS_RESOURCE", 1452); // This MUST be even.
define("NS_RESOURCE_TALK", 1453); // This MUST be the following odd integer.
$wgExtraNamespaces[NS_RESOURCE] = "Resource";
$wgExtraNamespaces[NS_RESOURCE_TALK] = "Resource_talk";

/**************************
 *  Include directory           *
 **************************/
$wgLveIncludes = $dir . 'includes/';

/**************************
 *  JSONLD Content           *
 **************************/
define('CONTENT_MODEL_JSONLD', 'rdf');
define('CONTENT_FORMAT_JSONLD', 'application/ld+json');
$wgAutoloadClasses['Lve\JsonldContent'] = $wgLveIncludes . 'LveJsonldContent.php';
$wgAutoloadClasses['Lve\JsonldContentHandler'] = $wgLveIncludes . 'LveJsonldContentHandler.php';
$wgNamespaceContentModels[NS_RESOURCE] = CONTENT_MODEL_JSONLD;
$wgContentHandlers[CONTENT_MODEL_JSONLD] = 'Lve\JsonldContentHandler';

/**************************
 *  Special pages         *
 **************************/
//$wgSpecialPages['OkOk'] = 'VEAdminClass'; # Okok is the real name of the Special page
$wgAutoloadClasses['Lve\AdminPage'] = $wgLveIncludes . 'SpecialLveAdminPage.php'; # Location of the SpecialVEAdminClass class (Tell MediaWiki to load this file)
$wgSpecialPages['AdminPage'] = 'Lve\AdminPage'; # Tell MediaWiki about the new special page and its class name
$wgAutoloadClasses['Lve\NamespacesPage'] = $wgLveIncludes . 'SpecialLveNamespacesPage.php';
$wgSpecialPages['EditNamespaces'] = 'Lve\NamespacesPage';
$wgAutoloadClasses['Lve\ImportPage'] = $wgLveIncludes . 'SpecialLveImportPage.php';
$wgSpecialPages['ImportVocabularies'] = 'Lve\ImportPage';
$wgAutoloadClasses['Lve\GetPage'] = $wgLveIncludes . 'SpecialLveGetPage.php';
$wgSpecialPages['GetResource'] = 'Lve\GetPage';

/**************************
 *  Autoload classes      *
 **************************/
$wgAutoloadClasses['Lve\Arc2Store'] = $wgLveIncludes . 'LveArc2Store.php';
$wgAutoloadClasses['Lve\TitleUtil'] = $wgLveIncludes . 'LveTitleUtil.php';
$wgAutoloadClasses['Lve\Resource'] = $wgLveIncludes . 'LveResource.php';
// $wgAutoloadClasses['Lexicalizer'] = $wgLveIncludes . 'Lexicalizer.php';
$wgAutoloadClasses['Lve\EditResource'] = $wgLveIncludes . 'LveEditResource.php';

/**************************
 *  Hooks                 *
 **************************/

$wgHooks['CustomEditor'][] = function (Article $article, User $user) {
	global $wgOut;
	if ($article->getPage()->getTitle()->getNamespace() == NS_RESOURCE) {
		$editor = new Lve\EditResource($article, $user);
		$editor->edit();
		return false;
	}
};

$wgHooks['AlternateEdit'][] = "Lve\EditResource::onAlternateEdit";
$wgHooks['EditPage::showEditForm:fields'][] = "Lve\EditResource::showEditFormFields";
$wgHooks['EditFormPreloadText'][] = 'Lve\EditResource::onEditFormPreloadText';
$wgHooks['PageContentSaveComplete'][] = 'Lve\EditResource::onPageContentSaveComplete';

// $wgHooks['EditFilterMergedContent'][] = 'VEHooksClass::onEditFilterMergedContent';

// hooks to the editpage
// $wgHooks['EditFormInitialText'][] = 'Edit::onEditFormInitialText';
// $wgHooks['PageContentSave'][] = 'Edit::onPageContentSave';
// $wgHooks['PageContentSaveComplete'][] = 'Edit::onPageContentSaveComplete';

// $wgHooks['TitleMove'][] = 'Edit::onTitleMove';
// $wgHooks['ArticleDelete'][] = 'Edit::onArticleDelete';

// }
