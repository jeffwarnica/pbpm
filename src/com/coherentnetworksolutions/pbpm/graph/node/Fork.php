<?php

namespace com\coherentnetworksolutions\pbpm\graph\node;

use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;
use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\graph\exe\Token;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;

/*
 * specifies configurable fork behaviour.
 * <p>
 * the fork can behave in two ways:
 * <ul>
 * <li>without configuration, the fork spawns one new child token over each
 * leaving transition.</li>
 * <li>with a script, the fork evaluates the script to obtain the names of
 * leaving transitions to take. the script must have exactly one variable with
 * 'write' access. the script has to assign a {@link Collection} of transition
 * names ({@link String}) to that variable.</li>
 * </ul>
 * </p>
 * <p>
 * if these behaviors do not cover your needs, consider writing a custom
 * {@link ActionHandler}.
 * </p>
 * @entity
 */
class Fork extends Node {
	
	/**
	 * a script that calculates the transitionNames at runtime.
	 */
	private $script;
	public function __construct($name = null) {
		parent::__construct($name);
	}
	
	// public function getNodeType() {
	// return NodeType.Fork;
	// }
	public function read(\DOMElement $forkElement, JpdlXmlReader $jpdlReader) {
		// nothing to read
	}
	public function execute(ExecutionContext $executionContext) {
		$transitionNames = [];
		// phase one: determine leaving transitions
		if (is_null($this->script)) {
			// by default, take all leaving transitions
			$transitions = $this->getLeavingTransitions();
			$iter = $transitions->getIterator();
			while ( $iter->valid() ) {
				$transition = $iter->current();
				$transitionNames[] = $transition->getName();
				$iter->next();
			}
		} else {
			// script evaluation selects transitions to take
			$transitionNames = $this->evaluateScript($executionContext);
		}
		
		// lock the arriving token to prevent application code from signaling tokens
		// parked in a fork
		// the corresponding join node unlocks the token after joining
		// https://jira.jboss.com/jira/browse/JBPM-642
		$token = $executionContext->getToken();
		$token->lock($this->__toString());
		
		// phase two: create child token for each selected transition
		$childTokens = new ArrayCollection();
		foreach ($transitionNames as $transitionName) {
			$childToken = $this->createForkedToken($token, $transitionName);
			$childTokens->offsetSet($transitionName, $childToken);
		}
		
		// phase three: branch child tokens from the fork into the transitions
		$iter = $childTokens->getIterator();
		while ( $iter->valid() ) {
			$transitionName = $iter->key();
			$childToken = $iter->current();
			$this->leave(new ExecutionContext($childToken), $transitionName);
			$iter->next();
		}
	}
	
	/**
	 * evaluates script and retrieves the names of leaving transitions.
	 * @TODO implement
	 */
	private function evaluateScript(ExecutionContext $executionContext) {
		throw new \Exception("UNIMPLEMENTED");
		// Map outputMap = script.eval(executionContext);
		// if (outputMap.size() == 1) {
		// // interpret single output value as collection
		// Object result = outputMap.values().iterator().next();
		// if (result instanceof Collection) return (Collection) result;
		// }
		// throw new JbpmException("expected " + script
		// + " to write one collection variable, output was: " + outputMap);
	}
	protected function createForkedToken(Token $parent, $transitionName) {
		// instantiate the child token
		return new Token($parent, $this->getTokenName($parent, $transitionName));
	}
	protected function getTokenName(Token $parent, $transitionName) {
		if ($transitionName != "") {
			// use transition name, if not taken already
			if (!$parent->hasChild($transitionName)) {
				return $transitionName;
			}
			
			$tokenText = "";
			$suffix = 2;
			do {
				$tokenText .= $suffix++;
				$tokenName = $tokenText;
			} while ( $parent->hasChild($tokenName) );
			
			return $tokenName;
		} else {
			// no transition name
			$childTokens = $parent->getChildren();
			return sizeof($childTokens) + 1;
		}
	}
	public function getScript() {
		return $this->script;
	}
	public function setScript($script) {
		$this->script = $script;
	}
}
