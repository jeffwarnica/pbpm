<?php
namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\GraphElement;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Task;
use com\coherentnetworksolutions\pbpm\context\exe\ContextInstance;

class ExecutionContext { 
	

	/**
	 * @var Token
	 */
	
	protected $token;
	
	/**
	 * @var Event
	 */
	protected $event;
	
	/**
	 * @var GraphElement
	 */
	protected $eventSource;

	/**@var Action **/
	protected $action;
	
	/**@var Throwable  **/
	protected $exception;

	/**@var Transition**/
	protected  $transition;

	/**@var Node **/
	protected $transitionSource;

	/**@var Task **/
	protected $task;

	/**@var Timer **/
	protected $timer;

	/**@var TaskInstance **/
	protected $taskInstance;

	/**
	 * @ManyToOne(targetEntity="ProcessInstance",cascade={"persist"})
	 * @var ProcessInstance
	 */
	protected $subProcessInstance;

	/**
	 * @var \Logger $log
	 */
	protected $log;
	public function __construct($tokenOrCtx) {
		$this->log = \Logger::getLogger(__CLASS__);
		if ($tokenOrCtx instanceof Token) {
			$this->token = $tokenOrCtx;
			$this->subProcessInstance = $tokenOrCtx->getSubProcessInstance();
		} elseif ($tokenOrCtx instanceof ExecutionContext) {
			$this->token = $tokenOrCtx->getToken();
			$this->event = $tokenOrCtx->getEvent();
			$this->action = $tokenOrCtx->getAction();
		} else {
			throw new \Exception("Give me a eiher a Token or ExecutionContext, dumbass");
		}
	}
	
	public function getNode() {
		return $this->token->getNode();
	}
	
	public function getProcessDefinition() {
		$processInstance = $this->getProcessInstance();
		return (!is_null(processInstance) ? $processInstance->getProcessDefinition() : null);
	}
	
	public function setAction(Action $action = null) {
		$this->action = $action;
		if (!is_null($action)) {
			$this->event = $action->getEvent();
		}
	}
	
	public function getProcessInstance() {
		return $this->token->getProcessInstance();
	}
	
	public function __toString() {
		return "ExecutionContext([{$this->token->getName()}])";
	}
	
// 	// convenience methods //////////////////////////////////////////////////////
	
// 	/**
// 	 * set a process variable.
// 	 */
// 	public void setVariable(String name, Object value) {
// 		if (taskInstance != null) {
// 			taskInstance.setVariable(name, value);
// 		}
// 		else {
// 			getContextInstance().setVariable(name, value, token);
// 		}
// 	}
	
// 	/**
// 	 * get a process variable.
// 	 */
// 	public Object getVariable(String name) {
// 		return taskInstance != null ? taskInstance.getVariable(name)
// 		: getContextInstance().getVariable(name, token);
// 	}
	
	/**
	 * leave this node over the default transition. This method is only available
	 * on node actions. Not on actions that are executed on events. Actions on
	 * events cannot change the flow of execution.
	 */
	public function leaveNode($nodeTransitionNameOrNull = "") {
		$this->getNode()->leave($this, $nodeTransitionNameOrNull);
	}
	
	
// 	public ModuleDefinition getDefinition(Class clazz) {
// 		return getProcessDefinition().getDefinition(clazz);
// 	}
	
// 	public ModuleInstance getInstance(Class clazz) {
// 		if (token != null) {
// 			ProcessInstance processInstance = token.getProcessInstance();
// 			if (processInstance != null) return processInstance.getInstance(clazz);
// 		}
// 		return null;
// 	}
	
	/**
	 * @return ContextInstance
	 */
	public function getContextInstance() {
		return $this->getInstance('com\coherentnetworksolutions\pbpm\context\exe\ContextInstance');
	}
	
// 	public TaskMgmtInstance getTaskMgmtInstance() {
// 		return (TaskMgmtInstance) getInstance(TaskMgmtInstance.class);
// 	}
	
// 	public JbpmContext getJbpmContext() {
// 		return JbpmContext.getCurrentJbpmContext();
// 	}
	
// 	// getters and setters //////////////////////////////////////////////////////
	
	public function setTaskInstance(TaskInstance $taskInstance) {
		$this->taskInstance = $taskInstance;
		$this->task = (!is_null($taskInstance)? $taskInstance->getTask() : null);
	}
	
	/**
	 * @return Token
	 */
	public function getToken() {
		$this->log->debug("EXEC getToken()");
		return $this->token;
	}
	
	public function getAction() {
		return $this->action;
	}
	
	public function getEvent() {
		return $this->event;
	}
	
	public function setEvent(Event $event = null) {
		$this->event = $event;
	}
	
	public function getException() {
		return $this->exception;
	}
	
	public function setException(\Exception $exception) {
		$this->exception = $exception;
	}
	
	public function getTransition() {
		return $this>transition;
	}
	
	public function setTransition(Transition $transition = null) {
		$this->transition = $transition;
	}
	
	public function getTransitionSource() {
		return $this->transitionSource;
	}
	
	public function setTransitionSource(Node $transitionSource = null) {
		$this->transitionSource = $transitionSource;
	}
	
	public function getEventSource() {
		return $this->eventSource;
	}
	
	public function setEventSource(GraphElement $eventSource = null) {
		$this->eventSource = $eventSource;
	}
	
	public function getTask() {
		return $this->task;
	}
	
	public function setTask(Task $task) {
		$this->task = $task;
	}
	
	public function getTaskInstance() {
		return $this->taskInstance;
	}
	
	public function getSubProcessInstance() {
		return $this->subProcessInstance;
	}
	
	public function setSubProcessInstance(ProcessInstance $subProcessInstance = null) {
		$this->subProcessInstance = $subProcessInstance;
	}
	
	public function getTimer() {
		return $this->timer;
	}
	
	public function setTimer(Timer $timer = null) {
		$this->timer = $timer;
	}
	
// 	// thread local execution context
	
// 	static ThreadLocal threadLocalContextStack = new ThreadLocal() {
// 		protected Object initialValue() {
// 			return new ArrayList();
// 		}
// 	};
	
// 	static List getContextStack() {
// 		return (List) threadLocalContextStack.get();
// 	}
	
// 	public static void pushCurrentContext(ExecutionContext executionContext) {
// 		getContextStack().add(executionContext);
// 	}
	
// 	public static void popCurrentContext(ExecutionContext executionContext) {
// 		List stack = getContextStack();
// 		int index = stack.lastIndexOf(executionContext);
// 		if (index == -1) {
// 			log.warn(executionContext + " was not found in thread-local stack;"
// 					+ " do not access ExecutionContext instances from multiple threads");
// 		}
// 		else {
// 			if (index < stack.size() - 1) {
// 				log.warn(executionContext + " was not popped in reverse push order;"
// 						+ " make sure you pop every context you push");
// 			}
// 			// remove execution context from stack, no matter what
// 			stack.remove(index);
// 		}
// 	}
	
// 	public static ExecutionContext currentExecutionContext() {
// 		List stack = getContextStack();
// 		return stack.isEmpty() ? null : (ExecutionContext) stack.get(stack.size() - 1);
// 	}
	
	
}