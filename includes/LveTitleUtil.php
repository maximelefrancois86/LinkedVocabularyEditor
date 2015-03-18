<?php

namespace Lve;

use \Title;

/**
 * from mediawiki Title objects to uri
 */
class TitleUtil {

	public static function allResourceTitles() {
		$dbr = wfGetDB(DB_SLAVE);
		$conds = array(
			'page_namespace' => NS_RESOURCE,
			'page_title >= ""',
			'page_is_redirect' => 0,
		);

		$res = $dbr->select('page',
			array('page_namespace', 'page_title', 'page_is_redirect', 'page_id'),
			$conds,
			__METHOD__,
			array(
				'ORDER BY' => 'page_title',
				'USE INDEX' => 'name_title',
			)
		);
		if ($res->numRows() > 0) {
			while ($s = $res->fetchObject()) {
				$titles[] = Title::newFromRow($s);
			}
		} else {
			$titles = array();
		}
		return $titles;
	}
}