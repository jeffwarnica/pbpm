<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;

/**
 *
 * @todo : change to JOINED
 *       @Entity
 *       @InheritanceType("SINGLE_TABLE")
 *       @DiscriminatorColumn(name="discr", type="string")
 */
abstract class GraphElement {
	
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
	 * @Column(type="string", nullable=true)
	 *
	 * @var string
	 *
	 */
	protected $name = null;
	
	/**
	 * @Column(type="string", nullable=true)
	 *
	 * @var string
	 *
	 */
	protected $description = null;
	
	/**
	 * @OneToOne(targetEntity="GraphElement")
	 * 1:1, self referential magic:
	 * @JoinColumn(name="owningProcessDefinition_id", referencedColumnName="id")
	 *
	 * @var ProcessDefinition
	 */
	protected $processDefinition = null;
	
	/**
	 * @OneToMany(targetEntity="Event", mappedBy="graphElement",cascade={"persist"})
	 *
	 * @var ArrayCollection
	 */
	protected $events;
	
	/**
	 * @OneToMany(targetEntity="ExceptionHandler",cascade={"persist"},mappedBy="graphElement")
	 *
	 * @var ArrayCollection
	 */
	protected $exceptionHandlers;
	
	/**
	 *
	 * @var \Logger
	 */
	protected $log;
	public function __construct($name) {
		$this->log = \Logger::getLogger(__CLASS__);
		$this->setName($name);
		$this->events = new ArrayCollection();
		$this->exceptionHandlers = new ArrayCollection();
	}
	
	/**
	 * indicative set of event types supported by this graph element.
	 * this is currently only used by the process designer to know which
	 * event types to show on a given graph element. in process definitions
	 * and at runtime, there are no contstraints on the event-types.
	 */
	public abstract function getSupportedEventTypes();
	
	/**
	 * gets the events, keyd by eventType (java.lang.String).
	 *
	 * @return array
	 */
	public function getEvents() {
		return $this->events;
	}
	public function hasEvents() {
		return ($this->events->count() > 0);
	}
	
	/**
	 *
	 * @param string $eventType        	
	 * @return Event
	 */
	public function getEvent($eventType) {
		return $this->events->get($eventType);
	}
	public function hasEvent($eventType) {
		return $this->events->containsKey($eventType);
	}
	public function addEvent(Event $event = null) {
		if (is_null($event))
			throw new \Exception("can't add a null event to a graph element");
		if (is_null($event->getEventType()))
			throw new \Exception("can't add an event without an eventType to a graph element");
		$this->events->set($event->getEventType(), $event);
		$event->setGraphElement($this);
		return $event;
	}
	public function removeEvent(Event $event = null) {
		if (is_null($event))
			throw new \Exception("can't remove a null event from a graph element");
		if (is_null($event->getEventType()))
			throw new \Exception("can't remove an event without an eventType from a graph element");
		$removed = $this->events[$event->getEventType()];
		$removed->graphElement = null;
		$this->events->offsetUnset($event->getEventType());
		return $removed;
	}
	public function read(\DOMElement $nodeElement, JpdlXmlReader $jpdlXmlReader) {
		$this->name = $nodeElement->getAttribute("name");
	}
	
	// // exception handlers ///////////////////////////////////////////////////////
	
	/**
	 * is the list of exception handlers associated to this graph element.
	 */
	public function getExceptionHandlers() {
		return $this->exceptionHandlers;
	}
	
	/**
	 *
	 * @param ExceptionHandler $exceptionHandler        	
	 * @return ExceptionHandler
	 */
	public function addExceptionHandler(ExceptionHandler $exceptionHandler) {
		$this->exceptionHandlers->add($exceptionHandler);
		$exceptionHandler->clearGraphElement();
		$exceptionHandler->setGraphElement($this);
		return $exceptionHandler;
	}
	public function removeExceptionHandler(ExceptionHandler $exceptionHandler) {
		if ($this->exceptionHandlers->remove($exceptionHandler)) {
			$exceptionHandler->graphElement = null;
		}
	}
	
	// public void reorderExceptionHandler(int oldIndex, int newIndex) {
	// if ( (exceptionHandlers!=null)
	// && (Math.min(oldIndex, newIndex)>=0)
	// && (Math.max(oldIndex, newIndex)<exceptionHandlers.size()) ) {
	// Object o = exceptionHandlers.remove(oldIndex);
	// exceptionHandlers.add(newIndex, o);
	// } else {
	// throw new IndexOutOfBoundsException("couldn't reorder element from index '"+oldIndex+"' to index '"+newIndex+"' in exceptionHandler-list '"+exceptionHandlers+"'");
	// }
	// }
	
	// // event handling ///////////////////////////////////////////////////////////
	public function fireEvent($eventType, ExecutionContext $executionContext) {
		$token = $executionContext->getToken();
		
		$this->log->debug("EXEC event [{$eventType}] on [{$this}] for [{$token}]");
		try {
			$executionContext->setEventSource($this);
			$this->fireAndPropagateEvent($eventType, $executionContext);
		} finally {
			$executionContext->setEventSource(null);
		}
	}
	public function fireAndPropagateEvent($eventType, ExecutionContext $executionContext) {
		$this->log->debug("EXEC fireandPropagateEvent");
		// calculate if the event was fired on this element or if it was a propagated event
		$isPropagated = $this->__equals($executionContext->getEventSource());
		
		// execute static actions
		$event = $this->getEvent($eventType);
		if (!is_null($event)) {
			// update the context
			$executionContext->setEvent($event);
			// execute the static actions specified in the process definition
			$this->executeActions($event->getActions(), $executionContext, $isPropagated);
		}
		
		// execute the runtime actions
		$runtimeActions = $this->getRuntimeActionsForEvent($executionContext, $eventType);
		$this->executeActions($runtimeActions, $executionContext, $isPropagated);
		
		// remove the event from the context
		$executionContext->setEvent(null);
		
		// propagate the event to the parent element
		$parent = $this->getParent();
		if (!is_null($parent)) {
			$parent->fireAndPropagateEvent($eventType, $executionContext);
		}
	}
	protected function executeActions($actions = [], ExecutionContext $executionContext, $isPropagated) {
		if (sizeof($actions) > 0) {
			foreach ($actions as $action) {
				/** @var Action $action **/
				if ($action->acceptsPropagatedEvents() || (!$isPropagated)) {
					if ($action->isAsync()) {
						// @TODO: async / Message
						// Message continuationMsg = new ExecuteActionCommand(action, executionContext.getToken());
						// MessageService messageService = (MessageService) Services.getCurrentService(Services.SERVICENAME_MESSAGE);
						// messageService.send(continuationMsg);
					} else {
						$this->executeAction($action, $executionContext);
					}
				}
			}
		}
	}
	public function executeAction(Action $action, ExecutionContext $executionContext) {
		$this->log->debug("EXEC executeAction action: [{$action}]");
		$token = $executionContext->getToken();
		
		// create action log
		// $actionLog = new ActionLog(action);
		// token.startCompositeLog(actionLog);
		
		try {
			// if the action is associated to an event, the token needs to be locked.
			// conversely, if the action is the behavior of a node or the token is already locked,
			// the token does not need to be locked
			
			if (!is_null($executionContext->getEvent()) && $token->isLocked()) {
				$lockOwner = $action->__toString();
				$token->lock($lockOwner);
				try {
					$this->executeActionImpl($action, $executionContext);
				} finally {
					$token->unlock($lockOwner);
				}
			} else {
				$this->executeActionImpl($action, $executionContext);
			}
		} catch ( \Exception $exception ) {
			// NOTE that Error's are not caught because that might halt the JVM and mask the original Error
			$this->log->error("action threw exception: <<<" . $exception->getMessage() . ">>>", $exception);
			
			// log the action exception
			// $actionLog.setException(exception);
			
			// if an exception handler is available
			$this->raiseException($exception, $executionContext);
		} finally {
			$executionContext->setAction(null);
			// token.endCompositeLog();
		}
	}
	private function executeActionImpl(Action $action, ExecutionContext $executionContext) {
		$this->log->debug("EXEC executeActionImpl");
		// set context action
		$executionContext->setAction($action);
		try {
			// UserCodeInterceptor userCodeInterceptor = UserCodeInterceptorConfig.getUserCodeInterceptor();
			// if (userCodeInterceptor != null) {
			// userCodeInterceptor.executeAction(action, executionContext);
			// }
			// else {
			$action->execute($executionContext);
			// }
		} finally {
			// reset context action
			$executionContext->setAction(null);
		}
	}
	protected function getRuntimeActionsForEvent(ExecutionContext $executionContext, $eventType = "") {
		$runtimeActionsForEvent = [ ];
		$runtimeActions = $executionContext->getProcessInstance()->getRuntimeActions();
		if (sizeof($runtimeActions) > 0) {
			$iter = $runtimeActions->getIterator();
			while ( $iter->valid() ) {
				/**@var \com\coherentnetworksolutions\pbpm\graph\exe\RuntimeAction **/
				$runtimeAction = $iter->current();
				// if the runtime-action action is registered on this element and this eventType
				if (($this->__equals($runtimeAction->getGraphElement())) && ($eventType == $runtimeAction->getEventType())) {
					// ... add its action to the list of runtime actions
					$runtimeActionsForEvent[] = $runtimeAction->getAction();
				}
				$iter->next();
			}
		}
		return $runtimeActionsForEvent;
	}
	
	// /*
	// // the next instruction merges the actions specified in the process definition with the runtime actions
	// List actions = event.collectActions(executionContext);
	
	// // loop over all actions of this event
	// Iterator iter = actions.iterator();
	// while (iter.hasNext()) {
	// Action action = (Action) iter.next();
	// executionContext.setAction(action);
	
	// if ( (!isPropagated)
	// || (action.acceptsPropagatedEvents() ) ) {
	
	// // create action log
	// ActionLog actionLog = new ActionLog(action);
	// executionContext.getToken().startCompositeLog(actionLog);
	
	// try {
	// // execute the action
	// action.execute(executionContext);
	
	// } catch (Exception exception) {
	// // NOTE that Error's are not caught because that might halt the JVM and mask the original Error.
	// Event.log.error("action threw exception: "+exception.getMessage(), exception);
	
	// // log the action exception
	// actionLog.setException(exception);
	
	// // if an exception handler is available
	// event.graphElement.raiseException(exception, executionContext);
	// } finally {
	// executionContext.getToken().endCompositeLog();
	// }
	// }
	// }
	// }
	// */
	
	/**
	 * throws an ActionException if no applicable exception handler is found.
	 * An ExceptionHandler is searched for in this graph element and then recursively up the
	 * parent hierarchy.
	 * If an exception handler is found, it is applied. If the exception handler does not
	 * throw an exception, the exception is considered handled. Otherwise the search for
	 * an applicable exception handler continues where it left of with the newly thrown
	 * exception.
	 */
	public function raiseException(/*Throwable*/ $exception, ExecutionContext $executionContext) {
		$isHandled = false;
		if (sizeof($this->exceptionHandlers) > 0) {
			try {
				$exceptionHandler = $this->findExceptionHandler($exception);
				if (!is_null($exceptionHandler)) {
					$executionContext->setException($exception);
					$exceptionHandler->handleException($executionContext);
					$isHandled = true;
				}
			} catch ( Exception $e ) {
				// NOTE that Error's are not caught because that might halt the JVM and mask the original Error.
				$exception = $e;
			}
		}
		
		if (!$isHandled) {
			$parent = $this->getParent();
			// if this graph element has a parent
			if ((!is_null($parent) && ($parent != $this))) {
				// action to the parent
				$parent->raiseException($exception, $executionContext);
			} else {
				// rollback the actions
				// rollbackActions(executionContext);
				
				// if there is no parent we need to throw an action exception to the client
				throw new DelegationException($exception);
			}
		}
	}
	protected function findExceptionHandler(\Exception $exception) {
		$exceptionHandler = null;
		
		$iter = $this->exceptionHandlers->getIterator();
		while ( $iter->valid() ) {
			$candidate = $iter->current();
			if ($candidate->matches($exception)) {
				$exceptionHandler = $candidate;
			}
			$iter->next();
		}
		return $exceptionHandler;
	}
	
	/**
	 *
	 * @return Node
	 */
	public function getParent() {
		return $this->processDefinition;
	}
	
	/**
	 *
	 * @return ArrayCollection of all the parents of this graph element ordered by age.
	 */
	public function getParents() {
		$parents = new ArrayCollection();
		$parent = $this->getParent();
		if (!is_null($parent)) {
			$this->parent->addParentChain($parents);
		}
		return $parents;
	}
	
	/**
	 *
	 * @return ArrayCollection this graph element plus all the parents ordered by age.
	 */
	public function getParentChain() {
		$this->log->debug(">getParentChain()");
		$parents = new ArrayCollection();
		$this->addParentChain($parents);
		$this->log->debug("<getParentChain() Size is: [" . sizeof($parents) . "]");
		return $parents;
	}
	protected function addParentChain(ArrayCollection $parentChain) {
		$parentChain->add($this);
		$parent = $this->getParent();
		if (!is_null($parent)) {
			$parent->addParentChain($parentChain);
		}
	}
	public function __toString() {
		$className = get_class($this);
		if ($this->name != "") {
			$className = $className . "({$this->name})";
		} else {
			$className = $className . "(" . spl_object_hash($this) . ")";
		}
		return $className;
	}
	
	// // equals ///////////////////////////////////////////////////////////////////
	// // hack to support comparing hibernate proxies against the real objects
	// // since this always falls back to ==, we don't need to overwrite the hashcode
	public function __equals($o) {
		return ($this == $o);
	}
	
	// // getters and setters //////////////////////////////////////////////////////
	public function getId() {
		return $this->id;
	}
	public function getName() {
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
	}
	public function getDescription() {
		return $this->description;
	}
	public function setDescription($desc) {
		$this->description = $desc;
	}
	public function getProcessDefinition() {
		return $this->processDefinition;
	}
	public function setProcessDefinition(ProcessDefinition $processDefinition) {
		$this->processDefinition = $processDefinition;
	}
	
	// // logger ///////////////////////////////////////////////////////////////////
	// private static final Log log = LogFactory.getLog(GraphElement.class);
}