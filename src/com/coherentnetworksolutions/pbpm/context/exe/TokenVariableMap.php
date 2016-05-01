<?php

namespace com\coherentnetworksolutions\pbpm\context\exe;

use com\coherentnetworksolutions\pbpm\graph\exe\Token;

/**
 * is a jbpm-internal map of variableInstances related to one {@link Token}.
 * Each token has it's own map of variableInstances, thereby creating hierarchy
 * and scoping of process variableInstances.
 * @entity
 */
class TokenVariableMap extends VariableContainer {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * @Column(type="integer")
	 * 
	 * @var int
	 */
	protected $version = -1;
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\exe\Token",cascade={"persist"})
	 * 
	 * @var Token`
	 */
	protected $token;
	/**
	 * @ManyToOne(targetEntity="ContextInstance",cascade={"persist"})
	 * 
	 * @var ContextInstance
	 */
	protected $contextInstance;
	
	/**
	 * @var \Logger
	 */
	protected $log;
	public function __construct(Token $token, ContextInstance $contextInstance) {
		parent::__construct();
		$this->token = $token;
		$this->contextInstance = $contextInstance;
	}
	public function addVariableInstance(VariableInstance $variableInstance) {
		parent::addVariableInstance($variableInstance);
		$variableInstance->setTokenVariableMap($this);
	}
	public function __toString() {
		return "TokenVariableMap" . (!is_null($this->token) ? "{$this->token->getName()})" : '@' + $this->hashCode());
	}
	
	// protected ////////////////////////////////////////////////////////////////
	protected function getParentVariableContainer() {
		$parentToken = $this->token->getParent();
		return (!is_null($parentToken) ? $this->contextInstance->getTokenVariableMap($parentToken) : null);
	}
	
	// getters and setters //////////////////////////////////////////////////////
	public function getContextInstance() {
		return $this->contextInstance;
	}
	public function getToken() {
		return $this->token;
	}
	public function getVariableInstances() {
		return $this->variableInstances;
	}
}
