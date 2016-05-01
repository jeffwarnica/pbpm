<?php 
namespace com\coherentnetworksolutions\pbpm\context\def;

use com\coherentnetworksolutions\pbpm\module\def\ModuleDefinition;
use com\coherentnetworksolutions\pbpm\context\exe\ContextInstance;

/**
 * @entity
 */
class ContextDefinition extends ModuleDefinition {

	/**
	 * @return ContextInstance
	 */
  public function createInstance() {
    return new ContextInstance();
  }
}
