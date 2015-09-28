<?php

namespace Lve;
use EasyRdf\RdfNamespace;
use \Title;

class Resource {

	private $bnode = false;
	private $bnodeid = '';
	private $pname = '';
	private $uri = '';

	private function __construct($uri) {
		if (substr($uri, 0, 2) == "_:") {
			$this->bnode = true;
			$this->bnodeid = substr($uri, 2);
		} elseif (preg_match("#://#", $uri)) {
			$this->uri = $uri;
			$this->pname = rawurldecode(RdfNamespace::shorten($uri, true));
		} else {
			$this->pname = rawurldecode($uri);
			$this->uri = RdfNamespace::expand($uri);
		}
	}

	function isBNode() {
		return $this->bnode;
	}

	function getUri() {
		if ($this->bnode) {
			return "_:" . $this->bnodeid;
		} else {
			return $this->uri;
		}
	}

	function getFragment() {
		if ($this->bnode) {
			return $this->bnodeid;
		} else {
			return substr($this->pname, strpos($this->pname, ":") + 1);
		}
	}

	function getPName() {
		if ($this->bnode) {
			return "_:" . $this->bnodeid;
		} else {
			return $this->pname;
		}
	}

	function getTitle() {
		if ($this->bnode) {
			return Title::newFromText("Resource:-:" . $this->bnodeid);
		} else {
			return Title::newFromText("Resource:" . $this->pname);
		}
	}

	function getWikilink() {
		$title = $this->getTitle();
		if ($this->bnode) {
			return "[[" . $title->getFullText() . "|_:" . $this->bnodeid . "]]";
		} else {
			return "[[" . $title->getFullText() . "|" . $this->pname . "]]";
		}
	}

	function getExtLink() {
		if ($this->bnode) {
			return $this->getWikilink();
		} else {
			return "[" . $this->uri . " " . $this->uri . "]";
		}
	}

	static function get($uri) {
		return new self($uri);
	}

	static function getByTitle($title) {
		$text = $title->getText();
		if (substr($text, 0, 2) == "-:") {
			return new self("_:" . substr($text, 2));
		} else {
			$text = preg_replace('/(\s)/', '_', lcfirst($text));
			return new self(RdfNamespace::expand($text));
		}
	}

}