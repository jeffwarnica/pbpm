<?php
namespace com\coherentnetworksolutions\pbpm\graph\node;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
/**
 * An agent capable of resolving sub-process definitions given the information items in the
 * <code>sub-process</code> element taken from a process definition document.
 */
interface SubProcessResolver {

  /**
   * Resolves a sub-process definition given the information items in the
   * <code>sub-process</code> element.
   * @return ProcessDefinition
   */
  public function findSubProcess(\DOMElement $subProcessElement);

}
