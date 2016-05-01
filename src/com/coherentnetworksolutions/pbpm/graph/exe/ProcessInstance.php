<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\context\exe\ContextInstance;
use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\module\exe\ModuleInstance;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\pbpmContext;


/**
 * is one execution of a {@link org.jbpm.graph.def.ProcessDefinition}.
 * To create a new process
 * execution of a process definition, just use the {@link #ProcessInstance(ProcessDefinition)}.
 *
 * @entity
 */
class ProcessInstance {
	
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @var int
	 *
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
	protected $key;
	
	/**
	 * @Column(type="datetime", nullable=true)
	 */
	protected $start;
	
	/**
	 * @Column(type="datetime", nullable=true)
	 */
	protected $end;
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition",cascade={"persist"})
	 *
	 * @var ProcessDefinition
	 */
	protected $processDefinition;
	/**
	 * @ManyToOne(targetEntity="Token",cascade={"persist"})
	 *
	 * @var Token
	 */
	protected $rootToken;
	/**
	 * @ManyToOne(targetEntity="Token",cascade={"persist"})
	 *
	 * @var Token
	 */
	protected $superProcessToken;
	/**
	 * @Column(type="boolean") 
	 */
	protected $isSuspended = false;
	
	/**
	 * @OneToMany(targetEntity="\com\coherentnetworksolutions\pbpm\module\exe\ModuleInstance", mappedBy="processInstance",cascade={"persist"})
	 *
	 * @var ArrayCollection
	 */
	protected $instances;
	/**
	 * does not persist, but arraycollection for consistency
	 *
	 * @var ArrayCollection $transientInstances
	 */
	protected $transientInstances;
	
	/**
	* @OneToMany(targetEntity="\com\coherentnetworksolutions\pbpm\graph\exe\RuntimeAction", mappedBy="processDefinition",cascade={"persist"})
	*
	* @var ArrayCollection
	*/
	protected $runtimeActions;
	/**
	 * not persisted
	 */
	protected $cascadeProcessInstances;

	/**
	 * @var \Logger
	 */
	protected $log;
	
	/**
	 * creates a new process instance for the given process definition, puts the root-token (=main
	 * path of execution) in the start state and executes the initial node.
	 * In case the initial
	 * node is a start-state, it will behave as a wait state. For each of the optional module
	 * definitions contained in the {@link ProcessDefinition}, the corresponding module instance
	 * will be created.
	 *
	 * @param
	 *        	variables will be inserted into the context variables after the context submodule
	 *        	has been created and before the process-start event is fired, which is also before the
	 *        	execution of the initial node.
	 * @throws JbpmException if processDefinition is null.
	 */
	public function __construct(ProcessDefinition $processDefinition, $variables = array(), $key = null) {
		$this->instances = new ArrayCollection();
		$this->transientInstances = new ArrayCollection();
		$this->runtimeActions = new ArrayCollection();
		$this->cascadeProcessInstances = new ArrayCollection();
		
		$this->log = \Logger::getLogger(__CLASS__);
		
		// initialize the members
		$this->processDefinition = $processDefinition;
		$this->rootToken = new Token($this);
		$this->key = $key;
		
		// create the optional definitions
		$this->addInitialModuleDefinitions($processDefinition);
		
		// if this is created in the context of a persistent operation
		// Services.assignId(this);
		
		// add the creation log
		// $rootToken->addLog(new ProcessInstanceCreateLog());
		
		// set the variables
		$this->addInitialContextVariables($variables);
		
		$initialNode = $this->rootToken->getNode();
		$this->fireStartEvent($initialNode);
	}
	
	public function addInitialContextVariables($variables) {
		if (sizeof($variables) != 0){
			$contextInstance = $this->getContextInstance();
			if (!is_null($contextInstance))
				$contextInstance->addVariables($variables);
		}
	}
	public function addInitialModuleDefinitions(ProcessDefinition $processDefinition) {
		$definitions = $processDefinition->getDefinitions();
		// if the state-definition has optional definitions
		if (sizeof($definitions) > 0){
			$iter = $definitions->getIterator();
			while ( $iter->valid() ){
				/**@var \com\coherentnetworksolutions\pbpm\module\def\ModuleDefinition **/
				$definition = $iter->current();
				$instance = $definition->createInstance();
				$iter->next();
			}
			if (!is_null($instance)){
				$this->addInstance($instance);
			}
		}
	}
	
	public function fireStartEvent(Node $initialNode = null) {
		$this->start = new \DateTime();
		
		// fire the process start event
		if (!is_null($initialNode )) {
			$executionContext = new ExecutionContext($this->rootToken);
			$this->processDefinition->fireEvent(Event::EVENTTYPE_PROCESS_START, $executionContext);
		
			// execute the start node
			$initialNode->execute($executionContext);
		}
	}
	
	// optional module instances ////////////////////////////////////////////////
	
	/**
	 * adds the given optional module instance (bidirectional).
	 */
	public function addInstance(ModuleInstance $moduleInstance) {
		$this->instances->set(get_class($moduleInstance), $moduleInstance);
		$moduleInstance->setProcessInstance($this);
		return $moduleInstance;
	}
	
	// /**
	// * removes the given optional module instance (bidirectional).
	// */
	// public ModuleInstance removeInstance(ModuleInstance moduleInstance) {
	// if (moduleInstance == null) {
	// throw new IllegalArgumentException("module instance is null");
	// }
	
	// if (instances != null && instances.remove(moduleInstance.getClass().getName()) != null) {
	// moduleInstance.setProcessInstance(null);
	// return moduleInstance;
	// }
	// return null;
	// }
	
	/**
	 * looks up an optional module instance by its class.
	 *
	 * @return ModuleInstance
	 */
	public function getInstance($clazz) {
		if (is_object($clazz)){
			$className = get_class($clazz);
		}else{
			$className = $clazz;
		}
		if ($this->instances->contains($className)){
			return $this->instances->get($className);
		}
		
		// client requested a module instance that is not in the persistent map;
		// assume the client wants a transient instance
		if (!$this->transientInstances->contains($className)){
			// try {
			/**
			 *
			 * @var ModuleInstance
			 */
			$moduleInstance = new $className();
			$moduleInstance->setProcessInstance($this);
			// }
			// catch (InstantiationException e) {
			// throw new JbpmException("failed to instantiate " + moduleClass, e);
			// }
			// catch (IllegalAccessException e) {
			// throw new JbpmException(getClass() + " has no access to " + moduleClass, e);
			// }
			$this->transientInstances->set($className, $moduleInstance);
		}
		
		return $moduleInstance;
	}
	
	/**
	 * process instance extension for process variableInstances.
	 *
	 * @return ContextInstance
	 */
	public function getContextInstance() {
		$ctxInstance = $this->getInstance('com\coherentnetworksolutions\pbpm\context\exe\ContextInstance');
		$ctxInstance->setProcessInstance($this);
		return $ctxInstance;
	}
	
	/**
	* process instance extension for managing the tasks and actors.
	* @return TaskMgmtInstance
	*/
	public function getTaskMgmtInstance() {
		return $this->getInstance('com\coherentnetworksolutions\pbpm\taskmgmt\exe\TaskMgmtInstance');
	}
	
	/**
	* process instance extension for logging. Probably you don't need to access the logging
	* instance directly. Mostly, {@link Token#addLog(ProcessLog)} is sufficient and more
	* convenient.
	* @return LoggingInstance 
	*/
// 	public function getLoggingInstance() {
// 		return $this->getInstance(LoggingInstance.class);
// 	}
	
	// operations ///////////////////////////////////////////////////////////////
	
	public function signal($transitionNameOrObj = null) {
		$this->log->debug("EXEC signal [{$this}]");
		if ($this->hasEnded()) {
			debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			throw new \Exception("process instance ended at: " .$this->end->format('Y-m-d H:i:s') );
		}
		$this->rootToken->signal($transitionNameOrObj);
	}
	// /**
	// * instructs the main path of execution to continue by taking the default transition on the
	// * current node.
	// *
	// * @throws IllegalStateException if the token is not active.
	// */
	// public void signal() {
	// if (hasEnded()) throw new IllegalStateException("process instance has ended");
	// rootToken.signal();
	// }
	
	// /**
	// * instructs the main path of execution to continue by taking the specified transition on the
	// * current node.
	// *
	// * @throws IllegalStateException if the token is not active.
	// */
	// public void signal(String transitionName) {
	// if (hasEnded()) throw new IllegalStateException("process instance has ended");
	// rootToken.signal(transitionName);
	// }
	
	// /**
	// * instructs the main path of execution to continue by taking the specified transition on the
	// * current node.
	// *
	// * @throws IllegalStateException if the token is not active.
	// */
	// public void signal(Transition transition) {
	// if (hasEnded()) throw new IllegalStateException("process instance has ended");
	// rootToken.signal(transition);
	// }
	
	/**
	* ends (=cancels) this process instance and all the tokens in it.
	*/
	public function end() {
		// if already ended, do nothing
		if (!is_null($this->end)) return;
		
		// record the end time
		// the end time also indicates that this process instance has ended
		$this->end = new \DateTime();
		
		// end the main path of execution
		$this->rootToken->end();
		
		// fire the process-end event
		$this->processDefinition->fireEvent(Event::EVENTTYPE_PROCESS_END, new ExecutionContext($this->rootToken));
		
		// add the process instance end log
		$this->rootToken->addLog("PI::end()" /*new ProcessInstanceEndLog()*/);
		
		// Fetch this higher, rather than doing the work twice.
		$pbpmContext = pbpmContext::getCurrentContext();
		
		$this->log->debug("XXX PI::End. I think sPT is " . (is_null($this->superProcessToken) ? "NULL": "NOT NULL"));
		// is this a sub-process?
		if (!is_null($this->superProcessToken) && !($this->superProcessToken->hasEnded())) {
			// is message service available?
// 			MessageService messageService;
// 			if (jbpmContext != null && Configs.getBoolean("jbpm.sub.process.async")
// 			&& (messageService = jbpmContext.getServices().getMessageService()) != null) {
// 			// signal super-process token asynchronously to avoid stale state exceptions
// 			// due to concurrent signals to the super-process
// 			// https://jira.jboss.org/browse/JBPM-2948
// 			SignalTokenJob job = new SignalTokenJob(superProcessToken);
// 			job.setDueDate(new Date());
// 			job.setExclusive(true);
// 			messageService.send(job);
// 			} else {
				$this->log->debug("XXX I think I have a superProcessToken");
				$this->addCascadeProcessInstance($this->superProcessToken->getProcessInstance());
				// message service unavailable, signal super-process token synchronously
				$executionContext = new ExecutionContext($this->superProcessToken);
				$executionContext->setSubProcessInstance($this);
				$this->superProcessToken->signal($executionContext);
// 		}	
		}
		// cancel jobs associated to this process instance
		
		// is there an active context?
		if (!is_null($pbpmContext)) {
			//@todo Services????
// 			$services = $pbpmContext->getServices();
// 			PersistenceService persistenceService = services.getPersistenceService();
// 			// is persistence service available? if so, are there jobs to delete?
// 			if (persistenceService != null
// 			&& persistenceService.getJobSession().countDeletableJobsForProcessInstance(this) > 0) {
// 			// is message service available?
// 			MessageService messageService = services.getMessageService();
// 			if (messageService != null) {
// 			// cancel jobs asynchronously to avoid stale state exceptions due to job acquisition
// 			// https://jira.jboss.org/browse/JBPM-1709
// 			CleanUpProcessJob job = new CleanUpProcessJob(rootToken);
// 			job.setDueDate(new Date());
// 			messageService.send(job);
// 			}
// 			else {
// 			// is scheduler service available?
// 			SchedulerService schedulerService = services.getSchedulerService();
// 			if (schedulerService != null) {
// 			// give scheduler a chance to cancel timers
// 			schedulerService.deleteTimersByProcessInstance(this);
// 			}
// 			else {
// 			// just delete jobs straight from the database
// 			persistenceService.getJobSession().deleteJobsForProcessInstance(this);
// 			}
// 			}
// 			}
		}
	}
	
	// /**
	// * suspends this execution. This will make sure that tasks, timers and messages related to
	// * this process instance will not show up in database queries.
	// *
	// * @see #resume()
	// */
	// public void suspend() {
	// isSuspended = true;
	// rootToken.suspend();
	// }
	
	// /**
	// * resumes a suspended execution. All timers that have been suspended might fire if the
	// * duedate has been passed. If an admin resumes a process instance, the option should be
	// * offered to update, remove and create the timers and messages related to this process
	// * instance.
	// *
	// * @see #suspend()
	// */
	// public void resume() {
	// isSuspended = false;
	// rootToken.resume();
	// }
	
	// // runtime actions //////////////////////////////////////////////////////////
	
	/**
	* adds an action to be executed upon a process event in the future.
	*/
	public function addRuntimeAction(RuntimeAction $runtimeAction) {
		$this->runtimeActions->add($runtimeAction);
		$runtimeAction->setProcessInstance($this);
		return $runtimeAction;
	}
	
	/**
	* removes a runtime action.
	*/
	public function removeRuntimeAction(RuntimeAction $runtimeAction) {
		if ($this->runtimeActions->removeElement($runtimeAction)) {
			$runtimeAction->processInstance = null;
			return $runtimeAction;
		}
		return null;
	}
	
	/**
	* is the list of all runtime actions.
	*/
	public function getRuntimeActions() {
		return $this->runtimeActions;
	}
	
	// // various information retrieval methods ////////////////////////////////////
	
	/**
	* tells if this process instance is still active or not.
	*/
	public function hasEnded() {
		return !is_null($this->end);
	}
	
	/**
	* calculates if this process instance has still options to continue.
	*/
	public function isTerminatedImplicitly() {
		return (!$this->hasEnded() ? $this->rootToken->isTerminatedImplicitly() : true);
	}
	
	/**
	* looks up the token in the tree, specified by the slash-separated token path.
	*
	* @param string $tokenPath is a slash-separated name that specifies a token in the tree.
	* @return Token the specified token or null if the token is not found.
	*/
	public function findToken($tokenPath) {
		$this->log->debug("findToken($tokenPath)");
		if (!is_null($this->rootToken)) {
			$this->log->debug("\t has rootToken, asking it...");
			return $this->rootToken->findToken($tokenPath);
		} else {
			return null;
		}
	}
	
	// /**
	// * collects all instances for this process instance.
	// */
	// public List findAllTokens() {
	// List tokens = new ArrayList();
	// tokens.add(rootToken);
	// rootToken.collectChildrenRecursively(tokens);
	// return tokens;
	// }
	
	public function addCascadeProcessInstance(ProcessInstance $cascadeProcessInstance) {
		$this->cascadeProcessInstances->add($cascadeProcessInstance);
	}
	
	public function removeCascadeProcessInstances() {
		$removed = $this->cascadeProcessInstances;
		$this->cascadeProcessInstances->clear();
		return $removed;
	}
	
	// // equals ///////////////////////////////////////////////////////////////////
	
	// public boolean equals(Object o) {
	// if (o == this) return true;
	// if (!(o instanceof ProcessInstance)) return false;
	
	// ProcessInstance other = (ProcessInstance) o;
	// if (id != 0 && id == other.getId()) return true;
	
	// return key != null && key.equals(other.getKey())
	// && processDefinition.equals(other.getProcessDefinition());
	// }
	
	// public int hashCode() {
	// if (key == null) return super.hashCode();
	
	// int result = 295436291 + key.hashCode();
	// result = 1367411281 * result + processDefinition.hashCode();
	// return result;
	// }
	
	public function __toString() {
		$str = "ProcessInstance";
		if (!is_null($this->key)) { 
			$str .= "({$this->key})";
		} else {
			if ($this->id !=0) {
				$str .= "({$this->id})";
			} else {
				$str .= "@" . spl_object_hash($this);
			}
		}
		return $str;	
	}
	
	// // getters and setters //////////////////////////////////////////////////////
	public function getId() {
		return $this->id;
	}
	/**
	 * @return Token
	 */
	public function getRootToken() {
		return $this->rootToken;
	}
	public function getStart() {
		return $this->start;
	}
	public function getEnd() {
		return $this->end;
	}
	public function getInstances() {
		return $this->instances;
	}
	public function getProcessDefinition() {
		return $this->processDefinition;
	}
	public function getSuperProcessToken() {
		return $this->superProcessToken;
	}
	public function setSuperProcessToken(Token $superProcessToken) {
		$this->superProcessToken = $superProcessToken;
	}
	public function isSuspended() {
		return $this->isSuspended;
	}
	public function getVersion() {
		return $this->version;
	}
	public function setVersion($version) {
		$this->version = $version;
	}
	public function setEnd(\DateTime $end) {
		$this->end = $end;
	}
	public function setProcessDefinition(ProcessDefinition $processDefinition) {
		$this->processDefinition = $processDefinition;
	}
	public function setRootToken(Token $rootToken) {
		$this->rootToken = $rootToken;
	}
	public function setStart(\DateTime $start) {
		$this->start = $start;
	}
	
	/**
	 * a unique business key
	 */
	public function getKey() {
		return $this->key;
	}
	
	/**
	 * set the unique business key
	 */
	public function setKey($key) {
		$this->key = $key;
	}
}
