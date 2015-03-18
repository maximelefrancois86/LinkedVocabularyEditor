<?php
namespace Lve;

use ML\JsonLD\JsonLD;

class JsonldContentHandler extends \JsonContentHandler {

	public function __construct($modelId = CONTENT_MODEL_JSONLD) {
		parent::__construct($modelId, array(CONTENT_FORMAT_JSONLD));
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return 'Lve\JsonldContent';
	}

	/**
	 * Creates an empty VE_JsonldContent object.
	 *
	 * @since 1.21
	 *
	 * @return Content A new VE_JsonldContent object.
	 */
	public function makeEmptyContent() {
		$class = $this->getContentClass();
		return new $class(array(array("@graph" => array())));
	}

	public function supportsSections() {
		return false;
	}

}