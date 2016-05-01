<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\GraphElement;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
use PHPMD\ProcessingError;

/**
 * is an action added at runtime to the execution of one process instance.
 * @entity
 */
class RuntimeAction {
	
	/**
	 * @Column(type="integer")
	 * @Id
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
	 * @ManyToOne(targetEntity="processInstance")
	 * 
	 * @var ProcessInstance
	 */
	protected $processInstance = null;
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\def\GraphElement")
	 * 
	 * @var GraphElement
	 */
	protected $graphElement;
	/**
	 * @Column(type="string", nullable=true);
	 */
	protected $eventType;
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\def\Action")
	 * 
	 * @var Action`
	 */
	protected $action;

	/**
	 * @var \Logger
	 */
	protected $log;
	
	
	/**
	 * creates a runtime action.
	 * Look up the event with
	 * {@link GraphElement#getEvent(String)} and the action with
	 * {@link ProcessDefinition#getAction(String)}. You can only lookup named
	 * actions easily.
	 */
	public function __construct($event_or_graphelement = null, Action $action = null, $eventType = "") {
		$this->log = \Logger::getLogger(__CLASS__);
		if (is_null($event_or_graphelement)) { 
			return; 
		}
		if ($event_or_graphelement instanceof GraphElement) {
			$this->graphElement = $event_or_graphelement;
			$this->eventType = $eventType;
			$this->action = $action;
 		} elseif ($event_or_graphelement instanceof Event) {
 			$this->graphElement = $event_or_graphelement->getGraphElement();
 			$this->eventType = $event_or_graphelement->getEventType();
 			$this->action = $action;
 		} else{
 			throw new \Exception("First argument must be an Event or GraphElement");
 		}
 		$this->log->debug("created new RuntimeAction on ge:[{$this->graphElement}], et:[{$this->eventType}], a:[{$this->action}]");
	}
	
	// public RuntimeAction(GraphElement graphElement, String eventType,
	// Action action) {
	// this.graphElement = graphElement;
	// this.eventType = eventType;
	// this.action = action;
	// }
	
	// equals ///////////////////////////////////////////////////////////////////
	public function __equals($o) {
		if ($this == $o)
			return true;
		if (!($o instanceof Event))
			return false;
		
		/** @var RuntimeAction **/
		$other = $o;
		if ($this->id != 0 && $this->id == $other->getId())
			return true;
		
		return ($this->eventType == $other->getEventType()) && $this->graphElement->__equals($other->getGraphElement()) && $this->processInstance->__equals($other->getProcessInstance());
	}
	
	// public int hashCode() {
	// int result = 560044783 + eventType.hashCode();
	// result = 279308149 * result + graphElement.hashCode();
	// result = 106268467 * result + processInstance.hashCode();
	// return result;
	// }
	
	// getters and setters //////////////////////////////////////////////////////
	public function getId() {
		return $this->id;
	}
	public function getProcessInstance() {
		return $this->processInstance;
	}
	public function setProcessInstance(ProcessInstance $processInstance) {
		$this->processInstance = $processInstance;
	}
	public function getAction() {
		return $this->action;
	}
	public function getEventType() {
		return $this->eventType;
	}
	public function getGraphElement() {
		return $this->graphElement;
	}
}
