<?php

namespace com\coherentnetworksolutions\pbpm\graph\node;

use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;

/**
 * @Entity *
 */
class State extends Node {
	public function __construct($name = null) {
		parent::__construct($name);
	}
	public function execute(ExecutionContext $executionContext) {
		//nothing is supposed to happen.
	}
}