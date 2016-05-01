<?php

namespace com\coherentnetworksolutions\pbpm\context\exe;

use com\coherentnetworksolutions\pbpm\module\exe\ModuleInstance;
use com\coherentnetworksolutions\pbpm\graph\exe\Token;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * maintains all the key-variable pairs for a process instance.
 * You can obtain a ContextInstance
 * from a processInstance from a process instance like this :
 *
 * <pre>
 * ProcessInstance processInstance = ...;
 * ContextInstance contextInstance = processInstance.getContextInstance();
 * </pre>
 *
 * More information on context and process variableInstances can be found in <a
 * href="../../../../../userguide/en/html/reference.html#context">the userguide, section
 * context</a>
 *
 * @entity
 */
class ContextInstance extends ModuleInstance {
	
	// maps Tokens to TokenVariableMaps
	/**
	 * @OneToMany(targetEntity="TokenVariableMap", mappedBy="contextInstance")
	 *
	 * @var ArrayCollection
	 */
	protected $tokenVariableMaps;
	
	/**
	 * maps variable names (String) to values (Object)
	 * @var ArrayCollection $transientVariables
	 */
	protected $transientVariables;
	protected $updatedVariableContainers;
	
	public function __construct() {
		$this->log = \Logger::getLogger(__CLASS__);
		$this->tokenVariableMaps = new ArrayCollection();
		$this->transientVariables = new ArrayCollection();
	}
	
	// normal variableInstances (persistent) ////////////////////////////////////
	
	/**
	 * creates a variable on the root-token (= process-instance scope) and calculates the actual
	 * VariableInstance-type from the value.
	 */
	public function createVariable($name, $value, Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
		}
		$this->setVariableLocally($name, $value, $token);
	}
	
	/**
	 * sets the variable on the root token, creates the variable if necessary and calculates the
	 * actual VariableInstance-type from the value.
	 */
	public function setVariableLocally($name, $value, Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
			$this->setVariableLocally($name, $value, $token);
		}else{
			$tokenVariableMap = $this->createTokenVariableMap($token);
			$tokenVariableMap->setVariableLocally($name, $value);
		}
	}
	
	/**
	 * retrieves all the variableInstances in scope of the given token.
	 */
	public function getVariables(Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
		}
		$tokenVariableMap = $this->getTokenVariableMap($token);
		
		return (!is_null($tokenVariableMap) ? $tokenVariableMap->getVariables() : null);
	}
	
	/**
	 * adds all the variableInstances on the root-token (= process-instance scope).
	 */
	public function addVariables($variables, Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
		}
		$this->setVariables($variables, $token);
	}
	
	/**
	 * The method setVariables is the same as the {@link #addVariables(Map, Token)}, but it was
	 * added for more consistency.
	 */
	public function setVariables($variables, Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
			$this->setVariables($variables, $token);
		}else{
			$tokenVariableMap = $this->getOrCreateToklenVariableMap($token);
			$tokenVariableMap->setVariablesLocally($variables);
		}
	}
	
	/**
	 * retrieves a variable in the scope of the token.
	 * If the given token does not have a variable
	 * for the given name, the variable is searched for up the token hierarchy.
	 */
	public function getVariable($name, Token $token = null) {
		$this->log->debug("getVariable([{$name}]");
		if (is_null($token)){
			$token = $this->getRootToken();
		}
		
		$variable = null;
		$tokenVariableMap = $this->getTokenVariableMap($token);
		if (!is_null($tokenVariableMap)){
			$variable = $tokenVariableMap->getVariable($name);
		}
		return $variable;
	}
	
	/**
	 * retrieves a variable which is local to the token.
	 * this method was added for naming
	 * consistency. it is the same as {@link #getLocalVariable(String, Token)}.
	 */
	public function getVariableLocally($name, Token $token = null) {
		return $this->getVariable($name, $token);
	}
	
	/**
	 * sets a variable.
	 * If a variable exists in the scope given by the token, that variable is
	 * updated. Otherwise, the variable is created on the root token (=process instance scope).
	 */
	public function setVariable($name, $value, Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
		}
		// TokenVariableMap
		$tokenVariableMap = $this->getOrCreateTokenVariableMap($token);
		$tokenVariableMap->setVariable($name, $value);
	}
	
	/**
	 * checks if a variable is present with the given name in the scope of the token.
	 */
	public function hasVariable($name, Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
		}
		$hasVariable = false;
		$tokenVariableMap = $this->getTokenVariableMap($token);
		if (!is_null($tokenVariableMap)){
			$hasVariable = $tokenVariableMap->hasVariable($name);
		}
		return $hasVariable;
	}
	
	/**
	 * deletes a variable from the given token.
	 * For safety reasons, this method does not propagate
	 * the deletion to parent tokens in case the given token does not contain the variable.
	 */
	public function deleteVariable($name, Token $token = null) {
		if (is_null($token)){
			$token = $this->getRootToken();
		}
		$tokenVariableMap = $this->getTokenVariableMap($token);
		if (!is_null($tokenVariableMap)){
			$tokenVariableMap->deleteVariable($name);
		}
	}
	
	// // transient variableInstances //////////////////////////////////////////////
	
	// /**
	// * retrieves the transient variable for the given name.
	// */
	// public Object getTransientVariable(String name) {
	// Object transientVariable = null;
	// if (transientVariables != null) {
	// transientVariable = transientVariables.get(name);
	// }
	// return transientVariable;
	// }
	
	// /**
	// * sets the transient variable for the given name to the given value.
	// */
	// public void setTransientVariable(String name, Object value) {
	// if (transientVariables == null) {
	// transientVariables = new HashMap();
	// }
	// transientVariables.put(name, value);
	// }
	
	// /**
	// * tells if a transient variable with the given name is present.
	// */
	// public boolean hasTransientVariable(String name) {
	// if (transientVariables == null) {
	// return false;
	// }
	// return transientVariables.containsKey(name);
	// }
	
	/**
	* retrieves all the transient variableInstances map. note that no deep copy is performed,
	* changing the map leads to changes in the transient variableInstances of this context
	* instance.
	* @return ArrayCollection
	*/
	public function getTransientVariables() {
		return $this->transientVariables;
	}
	
	/**
	* replaces the transient variableInstances with the given map.
	*/
	public function setTransientVariables(ArrayCollection $transientVariables = null) {
		$this->transientVariables = $transientVariables;
	}
	
	// /**
	// * removes the transient variable.
	// */
	// public void deleteTransientVariable(String name) {
	// if (transientVariables == null) return;
	// transientVariables.remove(name);
	// }
	
	/**
	 *
	 * @return Token
	 */
	private function getRootToken() {
		return $this->processInstance->getRootToken();
	}
	
	/**
	 * searches for the first token-variable-map for the given token and creates it on the root
	 * token if it doesn't exist.
	 */
	public function getOrCreateTokenVariableMap(Token $token = null) {
		$this->log->debug(">getOrCreateTokenVariableMap()");
		// if the given token has a variable map
		/**@var TokenVariableMap  **/
		$tokenVariableMap = null;
		
		if (!is_null($token) && $this->tokenVariableMaps->offsetExists(spl_object_hash($token))){
			$this->log->debug("\t token isn't null, and offsetExists. ");
			$tokenVariableMap = $this->tokenVariableMaps->get(spl_object_hash($token));
		}else if (!$token->isRoot()){
			$this->log->debug("\t token isn't root, recurse to token->getParent ");
			$tokenVariableMap = $this->getOrCreateTokenVariableMap($token->getParent());
		}else{
			$this->log->debug("\t creating new TVM on token");
			$tokenVariableMap = $this->createTokenVariableMap($token);
		}
		
		return $tokenVariableMap;
	}
	private function createTokenVariableMap(Token $token) {
		$tokenVariableMap = $this->tokenVariableMaps->get(spl_object_hash($token));
		if (is_null($tokenVariableMap)){
			$this->log->debug("\tCreating new tvm, which will be set on TVMs[" . spl_object_hash($token) . "]");
			$tokenVariableMap = new TokenVariableMap($token, $this);
			$this->tokenVariableMaps->set(spl_object_hash($token), $tokenVariableMap);
		}
		return $tokenVariableMap;
	}
	
	/**
	* looks for the first token-variable-map that is found up the token-parent hierarchy.
	*/
	public function getTokenVariableMap(Token $token) {
		$tokenVariableMap = null;
		if (!is_null($this->tokenVariableMaps)) {
			$key = spl_object_hash($token);
			$this->log->debug("tvms is not null. Searching for key [{$key}] from keys: " . join(",", $this->tokenVariableMaps->getKeys()));
			if ($this->tokenVariableMaps->containsKey($key)) {
				$tokenVariableMap = $this->tokenVariableMaps->get($key);
			} else if (!$token->isRoot()) {
				$tokenVariableMap = $this->getTokenVariableMap($token->getParent());
			}
		}
		return $tokenVariableMap;
	}
	
	// public VariableInstance getVariableInstance(String name, Token token) {
	// VariableInstance variableInstance = null;
	// TokenVariableMap tokenVariableMap = getTokenVariableMap(token);
	// if (tokenVariableMap != null) {
	// tokenVariableMap.getVariableInstances();
	// }
	// return variableInstance;
	// }
	
	// public Map getTokenVariableMaps() {
	// return tokenVariableMaps;
	// }
	
	// public List getUpdatedVariableContainers() {
	// return updatedVariableContainers;
	// }
	
	/**
	 * Argh. "package" only visibility. 
	 * 
	 * @param VariableContainer $variableContainer
	 */
	public function addUpdatedVariableContainer(VariableContainer $variableContainer) {
		$this->updatedVariableContainers[] = $variableContainer;
	}
}
