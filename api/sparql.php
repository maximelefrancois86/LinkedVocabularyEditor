<?php

namespace Lve;
use \ARC2;

require 'databaseSettings.php';
require '../vendor/autoload.php';
require '../includes/LveArc2Store.php';
error_reporting(E_ALL^E_DEPRECATED);

$wgDBprefix = "ws";

$wgARC2StoreConfig = array(
	'db_host' => $wgDBserver,
	'db_name' => $wgDBname,
	'db_user' => $wgDBuser,
	'db_pwd' => $wgDBpassword,
	'store_name' => $wgDBprefix . 'arc2store', // Determines table prefix

	/* endpoint */
	'endpoint_features' => array(
		'select', 'construct', 'ask', 'describe',
		'load', 'insert', 'delete',
		//'dump', /* dump is a special command for streaming SPOG export */
	),
	'endpoint_timeout' => 60, /* not implemented in ARC2 preview */
	'endpoint_read_key' => '', /* optional */
//	'endpoint_write_key' => 'REPLACE_THIS_WITH_SOME_KEY', /* optional, but without one, everyone can write! */
	'endpoint_max_limit' => 250, /* optional */
);

/* instantiation */
$ep = ARC2::getStoreEndpoint($wgARC2StoreConfig);

/* request handling */
$ep->go();