<?php

namespace com\coherentnetworksolutions\pbpm\context\exe;


use com\coherentnetworksolutions\pbpm\graph\exe\Token;
/**
 * Here we diverge significantly from Java.
 * We just un/serialize variables. If its not a scalar, good luck.
 * @entity
 */
class VariableInstance {
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
	 * @Column(type="string", nullable=true)
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\exe\Token",cascade={"persist"})
	 * 
	 * @var Token
	 */
	protected $token;
	
	/**
	 * @ManyToOne(targetEntity="TokenVariableMap",cascade={"persist"})
	 * 
	 * @var TokenVariableMap
	 */
	protected $tokenVariableMap;
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance",cascade={"persist"})
	 * 
	 * @var ProcessInstance
	 */
	protected $processInstance;
	protected $value;
	// protected Converter converter;
	// protected Object valueCache;
	// protected boolean isValueCached;
	
	/**
	 *
	 * @var \Logger
	 */
	protected $log;
	public function __construct() {
		$this->log = \Logger::getLogger(__CLASS__);
	}
	
	public static function create(Token $token, $name, $value) {
		$variableInstance = self::createVariableInstance($value);
		
		$variableInstance->name = $name;
		if (!is_null($token)){
			$variableInstance->token = $token;
			$variableInstance->processInstance = $token->getProcessInstance();
			$token->addLog("Variable created: [$name]");
		}
		
		$variableInstance->setValue($value);
		return $variableInstance;
	}
	
	/**
	 *
	 * @param mixed $value        	
	 * @return VariableInstance
	 */
	public static function createVariableInstance($value) {
		return new VariableInstance();
	}
	
	// abstract methods /////////////////////////////////////////////////////////
	
	/**
	 * is true if this variable-instance supports the given value, false otherwise.
	 */
	public function isStorable($value) {
		return true;
	}
	
	/**
	 * is the value, stored by this variable instance.
	 */
	protected function getObject() {
		return unserialize($this->value);
	}
	
	/**
	 * stores the value in this variable instance.
	 */
	protected function setObject($value) {
		$this->value = serialize($value);
	}
	
	// variable management //////////////////////////////////////////////////////
	public function supports($value) {
		return true;
	}
	public function setValue($value) {
		$this->setObject($value);
	}
	public function getValue() {
		return unserialize($this->value);
	}
	public function removeReferences() {
		$this->tokenVariableMap = null;
		$this->token = null;
		$this->processInstance = null;
	}
	
	// utility methods /////////////////////////////////////////////////////////
	public function toString() {
		return (is_object($this->value) ? get_class($this->value) : gettype($this->value));
	}
	
	// getters and setters //////////////////////////////////////////////////////
	public function getName() {
		return $this->name;
	}
	public function getProcessInstance() {
		return $this->processInstance;
	}
	public function getToken() {
		return $this->token;
	}
	public function setTokenVariableMap(TokenVariableMap $tokenVariableMap) {
		$this->tokenVariableMap = $tokenVariableMap;
	}
}
