<?php

namespace com\coherentnetworksolutions\pbpm\context\exe;

/**
 * @entity*
 */
abstract class VariableContainer {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * @Column(type="array");
	 * @var array $variableInstances key, value
	 */
	protected $variableInstances = array ();
	
	// private static final long serialVersionUID = 520258491083406913L;
	
	/**
	 *
	 * @return VariableContainer
	 */
	protected abstract function getParentVariableContainer();
	
	/**
	 *
	 * @var \Logger
	 */
	protected $log;
	public function __construct() {
		$this->log = \Logger::getLogger(__CLASS__);
	}
	/**
	 *
	 * @return Token
	 */
	public abstract function getToken();
	
	// variables ////////////////////////////////////////////////////////////////
	public function getVariable($name) {
		$value = null;
		if ($this->hasVariableLocally($name)){
			$value = $this->getVariableLocally($name);
		}else{
			$parent = $this->getParentVariableContainer();
			if (!is_null($parent)){
				// check upwards in the token hierarchy
				$value = $parent->getVariable($name);
			}
		}
		return $value;
	}
	public function setVariable($name, $value) {
		$this->log->debug(">setVariable([$name], [$value]");
		$parent = $this->getParentVariableContainer();
		if ($this->hasVariableLocally($name) || is_null($parent)){
			$this->setVariableLocally($name, $value);
		}else{
			// propagate to parent variable container
			$parent->setVariable($name, $value);
		}
	}
	public function hasVariable($name) {
		// if the variable is present in the variable instances
		if ($this->hasVariableLocally($name))
			return true;
			
			// search in parent variable container
		$parent = $this->getParentVariableContainer();
		if (!is_null($parent))
			return $parent->hasVariable($name);
		
		return false;
	}
	public function deleteVariable($name) {
		if ($this->hasVariableLocally($name))
			$this->deleteVariableLocally($name);
	}
	
	/**
	 * adds all the given variables to this variable container.
	 * The method
	 * {@link #setVariables(Map)} is the same as this method, but it was added for naming
	 * consistency.
	 */
	public function addVariables($variables = array()) {
		$this->setVariables($variables);
	}
	
	/**
	 * adds all the given variables to this variable container.
	 * It doesn't remove any existing
	 * variables unless they are overwritten by the given variables. This method is the same as
	 * {@link #addVariables(Map)} and this method was added for naming consistency.
	 */
	public function setVariables($variables = array()) {
		foreach ($variables as $name => $value){
			$this->setVariable($name, $value);
		}
	}
	public function getVariables() {
		$variables = $this->getVariablesLocally();
		$parentContainer = $this->getParentVariableContainer();
		if (!is_null($parentContainer)){
			$parentVariables = $parentContainer->getVariablesLocally();
			$parentVariables->putAll($variables);
			$variables = $parentVariables;
		}
		return $variables;
	}
	public function getVariablesLocally() {
		$variables = array ();
		if (!is_null($this->variableInstances)){
			foreach ($this->variableInstances as $name => $value){
				if (!array_key_exists($name, $variables)){
					$variables[$name] = $value;
				}
			}
			return $variables;
		}
	}
	
	// local variable methods ///////////////////////////////////////////////////
	public function hasVariableLocally($name) {
		return array_key_exists($name, $this->variableInstances);
	}
	public function getVariableLocally($name) {
		$value = null;
		
		// if the variable is present in the variable instances
		if ($this->hasVariableLocally($name)){
			$value = $this->getVariableInstance($name)->getValue();
		}
		
		return $value;
	}
	public function deleteVariableLocally($name) {
		$this->deleteVariableInstance($name);
	}
	public function setVariableLocally($name, $value) {
		$this->log->debug("setVariableLocally([{$name}], [...])");
		if ($name == ""){
			throw new \Exception("variable name is null");
		}
		
		/**
		 *
		 * @var VariableInstance $variableInstance
		 */
		$variableInstance = $this->getVariableInstance($name);
		// if variable instance already exists and it does not support the new value
		if (!is_null($variableInstance) && !$variableInstance->supports($value)){
			// delete the old variable instance
			$this->log->debug($variableInstance->getToken() + " unsets [{$name}] due to type change");
			$this->deleteVariableInstance($name);
			$variableInstance = null;
		}
		
		if (!is_null($variableInstance)){
			$this->log->debug($variableInstance->getToken() + " sets [{$name}] to [{$value}]");
			$variableInstance->setValue($value);
		}else{
			$token = $this->getToken();
			$this->log->debug($token . " initializes [{$name}] to [{$value}]");
			$this->addVariableInstance(VariableInstance::create($token, $name, $value));
		}
	}
	// // local variable instances /////////////////////////////////////////////////
	
	public function getVariableInstance($name) {
		return (array_key_exists($name, $this->variableInstances) ?  $this->variableInstances[$name] : null);
	}
	
	public function getVariableInstances() {
		return $this->variableInstances;
	}
	
	public function addVariableInstance(VariableInstance $variableInstance) {
		$this->variableInstances[$variableInstance->getName()] = $variableInstance;
		
		// only register additions in the updated variable containers
		// because the registry is only used to check for non-persistable variables
		$contextInstance = $this->getContextInstance();
		if (!is_null($contextInstance)) { $contextInstance->addUpdatedVariableContainer($this); }
	}
		
// 		public void deleteVariableInstance(String name) {
// 		if (variableInstances != null) {
// 		VariableInstance variableInstance = (VariableInstance) variableInstances.remove(name);
// 		if (variableInstance != null) {
// 		// unlink variable
// 		variableInstance.removeReferences();
// 		// log variable deletion
// 		getToken().addLog(new VariableDeleteLog(variableInstance));
		
// 		// if a context is present and its logging service is not connected to the database
// 		JbpmContext jbpmContext = JbpmContext.getCurrentJbpmContext();
// 		if (jbpmContext != null
// 		&& !(jbpmContext.getServices().getLoggingService() instanceof DbLoggingService)) {
// 		// delete variable instance here before all references to it are lost
// 		Session session = jbpmContext.getSession();
// 		if (session != null) session.delete(variableInstance);
// 		}
// 		}
// 		}
// 	}	
	
	/**
	 * @return ContextInstance
	 */
	public function getContextInstance() {
		$token = $this->getToken();
		return (!is_null($token) ? $token->getProcessInstance()->getContextInstance() : null);
	}
	
	// /** @deprecated call {@link ContextInstance#getUpdatedVariableContainers()} instead */
	// public static Collection getUpdatedVariableContainers(ProcessInstance processInstance) {
	// return processInstance.getContextInstance().updatedVariableContainers;
	// }
	
	// private static final Log log = LogFactory.getLog(VariableContainer.class);
}
