<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\jpdl\xml\Parsable;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;
use com\coherentnetworksolutions\pbpm\graph\exe\Token;

/**
 * @Entity *
 */
class Node extends GraphElement implements Parsable {
	
	/**
	 * @OneToMany(targetEntity="Transition",mappedBy="from",cascade={"persist"})
	 *
	 * @var ArrayCollection
	 */
	protected $leavingTransitions;
	
	/**
	 *
	 * @var array of name->transition
	 */
	protected $leavingTransitionMap = array ();
	
	/**
	 * @OneToMany(targetEntity="Transition",mappedBy="to",cascade={"persist"})
	 *
	 * @var ArrayCollection
	 */
	protected $arrivingTransitions;
	
	/**
	 *
	 * @var Action
	 */
	protected $action;
	
	/**
	 *
	 * @var SuperState
	 */
	protected $superState = null;
	
	/**
	 * @Column(type="boolean")
	 *
	 * @var boolean
	 */
	protected $isAsync = false;
	
	// @noformat:on
	// event types //////////////////////////////////////////////////////////////
	public static $supportedEventTypes = [ 
			Event::EVENTTYPE_NODE_ENTER,
			Event::EVENTTYPE_NODE_LEAVE,
			Event::EVENTTYPE_BEFORE_SIGNAL,
			Event::EVENTTYPE_AFTER_SIGNAL 
	];
	
	/**
	 * Unorthodox, I grant.
	 * A mapping of the XML node names -> php class names
	 */
	public static $nodeTypes = [ 
			"start-state" => 'com\coherentnetworksolutions\pbpm\graph\node\StartState',
			"node" => 'com\coherentnetworksolutions\pbpm\graph\def\Node',
			"state" => 'com\coherentnetworksolutions\pbpm\graph\node\State',
			"fork" => 'com\coherentnetworksolutions\pbpm\graph\node\Fork',
			"super-state" => 'com\coherentnetworksolutions\pbpm\graph\def\SuperState',
			"process-state" => 'com\coherentnetworksolutions\pbpm\graph\node\ProcessState',
			"end-state" => 'com\coherentnetworksolutions\pbpm\graph\node\EndState',
			"task-node" => 'com\coherentnetworksolutions\pbpm\graph\node\TaskNode' 
	];
	
	// @formatter:off
	public function __construct($name = null) {
		parent::__construct($name);
		$this->leavingTransitions = new ArrayCollection();
		$this->arrivingTransitions = new ArrayCollection();
	}
	public function getSupportedEventTypes() {
		return self::$supportedEventTypes;
	}
	public function read(\DOMElement $nodeElement, JpdlXmlReader $jpdlXmlReader) {
		$this->log->debug(">Node::read()");
		$this->action = $jpdlXmlReader->readSingleAction($nodeElement);
		$this->log->debug("<Node::read()");
	}
	public function write(\DOMElement $nodeElement) {
		if (!is_null($this->action)) {
			$actionName = Action::getActionName(get_class($this->action));
			$actionElement = $nodeElement->ownerDocument->createElement($actionName);
			$nodeElement->appendChild($actionElement);
			$this->action->write($actionElement);
		}
	}
	
	// // leaving transitions //////////////////////////////////////////////////////
	
	/**
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getLeavingTransitions() {
		return $this->leavingTransitions;
	}
	
	/**
	 * are the leaving {@link Transition}s, mapped by their name (java.lang.String).
	 *
	 * @return array
	 */
	public function getLeavingTransitionsMap() {
		if (($this->leavingTransitionMap == null) && (sizeof($this->leavingTransitions) > 0)) {
			$this->leavingTransitionMap = array ();
			foreach ($this->leavingTransitions as $lt) {
				$this->leavingTransitionMap[$lt->getName()] = $lt;
			}
		}
		return $this->leavingTransitionMap;
	}
	
	/**
	 * creates a bidirection relation between this node and the given leaving transition.
	 *
	 * @throws IllegalArgumentException if leavingTransition is null.
	 * @return Transition
	 */
	public function addLeavingTransition(Transition $leavingTransition = null) {
		if (is_null($leavingTransition))
			throw new \Exception("can't add a null leaving transition to an node");
		$this->leavingTransitions->add($leavingTransition);
		$leavingTransition->setFrom($this);
		return $leavingTransition;
	}
	
	/**
	 * removes the bidirection relation between this node and the given leaving transition.
	 *
	 * @throws IllegalArgumentException if leavingTransition is null.
	 */
	public function removeLeavingTransition(Transition $leavingTransition) {
		if ($this->leavingTransitions->indexOf($leavingTransition) !== false) {
			$this->leavingTransitions->removeElement($leavingTransition);
			$leavingTransition->from = null;
		}
	}
	
	/**
	 * checks for the presence of a leaving transition with the given name.
	 *
	 * @return true if this node has a leaving transition with the given name,
	 *         false otherwise.
	 */
	public function hasLeavingTransition($transitionName) {
		if (sizeof($this->leavingTransitions) == 0)
			return false;
		return $this->getLeavingTransitionsMap()[$transitionName];
	}
	
	/**
	 * retrieves a leaving transition by name.
	 * note that also the leaving
	 * transitions of the supernode are taken into account.
	 *
	 * @return Transition
	 */
	public function getLeavingTransition($transitionName) {
		$this->log->debug("getLeavingTransition([$transitionName])");
		$iter = $this->leavingTransitions->getIterator();
		
		while ( $iter->valid() ) {
			$t = $iter->current();
			$this->log->debug("\t == [{$t->getName()}]?");
			if ($transitionName != "") {
				if ($transitionName == $t->getName()) {
					return $t;
				}
			} else {
				if ($t->getName() == "") {
					return $t;
				}
			}
			$iter->next();
		}
		$this->log->debug("\t Got this far???");
		if (!is_null($this->superState)) {
			return $this->superState->getLeavingTransition($transitionName);
		}
		return;
	}
	
	// /**
	// * true if this transition has leaving transitions.
	// */
	// public boolean hasNoLeavingTransitions() {
	// return ( ( (leavingTransitions == null)
	// || (leavingTransitions.size() == 0) )
	// && ( (superState==null)
	// || (superState.hasNoLeavingTransitions() ) ) );
	// }
	
	// /**
	// * generates a new name for a transition that will be added as a leaving transition.
	// */
	// public String generateNextLeavingTransitionName() {
	// String name = null;
	// if (leavingTransitions!=null) {
	// if (!containsName(leavingTransitions, null)) {
	// name = null;
	// } else {
	// int n = 1;
	// while (containsName(leavingTransitions, Integer.toString(n))) n++;
	// name = Integer.toString(n);
	// }
	// }
	// return name;
	// }
	
	// boolean containsName(List leavingTransitions, String name) {
	// Iterator iter = leavingTransitions.iterator();
	// while (iter.hasNext()) {
	// Transition transition = (Transition) iter.next();
	// if ( (name==null) && (transition.getName()==null) ) {
	// return true;
	// } else if ( (name!=null) && (name.equals(transition.getName())) ) {
	// return true;
	// }
	// }
	// return false;
	// }
	
	// // default leaving transition and leaving transition ordering ///////////////
	
	/**
	 * is the default leaving transition.
	 *
	 * @return Transition
	 */
	public function getDefaultLeavingTransition() {
		$this->log->debug(">getDefaultLeavingTransition() on node [{$this->getName()}]. size of LT's is: " . sizeof($this->leavingTransitions));
		$defaultTransition = null;
		if ($this->leavingTransitions->count() > 0) {
			$this->log->debug("<getDefaultLeavingTransition will return my ->first(). LT has a size of:" . sizeof($this->leavingTransitions));
			// $this->leavingTransitions->first();
			$defaultTransition = $this->leavingTransitions->current();
		} else if (!is_null($this->superState)) {
			$this->log->debug("<getDefaultLeavingTransition will return my superState->gDLT()");
			$defaultTransition = $this->superStatae->getDefaultLeavingTransition();
		}
		$this->log->debug("\tWhich is [{$defaultTransition}]");
		
		return $defaultTransition;
	}
	
	// /**
	// * moves one leaving transition from the oldIndex and inserts it at the newIndex.
	// */
	// public void reorderLeavingTransition( int oldIndex, int newIndex ) {
	// if ( (leavingTransitions!=null)
	// && (Math.min(oldIndex, newIndex)>=0)
	// && (Math.max(oldIndex, newIndex)<leavingTransitions.size()) ) {
	// Object o = leavingTransitions.remove(oldIndex);
	// leavingTransitions.add(newIndex, o);
	// }
	// }
	
	/**
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getLeavingTransitionsList() {
		return $this->leavingTransitions;
	}
	
	// // arriving transitions /////////////////////////////////////////////////////
	
	/**
	 * are the arriving transitions.
	 *
	 * @return ArrayCollection
	 */
	public function getArrivingTransitions() {
		return $this->arrivingTransitions;
	}
	
	/**
	 * add a bidirection relation between this node and the given arriving
	 * transition.
	 *
	 * @throws IllegalArgumentException if t is null.
	 * @return Transition
	 */
	public function addArrivingTransition(Transition $arrivingTransition) {
		// $this->log->debug("addArrivingTransition( from: [{$arrivingTransition->getFrom()->getName()}] TO--> [{$this->name}])");
		if (is_null($arrivingTransition))
			throw new \Exception("can't add a null arrivingTransition to a node");
		$this->arrivingTransitions->add($arrivingTransition);
		$arrivingTransition->setTo($this);
		return $arrivingTransition;
	}
	
	/**
	 * removes the bidirection relation between this node and the given arriving
	 * transition.
	 *
	 * @throws IllegalArgumentException if t is null.
	 */
	public function removeArrivingTransition(Transition $arrivingTransition) {
		if ($this->arrivingTransitions->indexOf($arrivingTransition) !== false) {
			$this->arrivingTransitions->removeElement($arrivingTransition);
			$arrivingTransition->to = null;
		}
	}
	
	// // various //////////////////////////////////////////////////////////////////
	
	/**
	 * is the {@link SuperState} or the {@link ProcessDefinition} in which this
	 * node is contained.
	 *
	 * @return GraphElement
	 */
	public function getParent() {
		$this->log->debug(">getParent[{$this->name}]. Is my superState null? " . (is_null($this->superState) ? "NULL" : "NOT NULL"));
		
		$parent = $this->processDefinition;
		if (!is_null($this->superState)) {
			$parent = $this->superState;
		}
		$this->log->debug("\tSo parent is [{$parent->getName()}]");
		return $parent;
	}
	
	// // behaviour methods ////////////////////////////////////////////////////////
	
	/**
	 * called by a transition to pass execution to this node.
	 */
	public function enter(ExecutionContext $executionContext) {
		$this->log->debug("XXX enter [{$this}]");
		$token = $executionContext->getToken();
		
		// update the runtime context information
		$token->setNode($this);
		
		// fire the leave-node event for this node
		$this->fireEvent(Event::EVENTTYPE_NODE_ENTER, $executionContext);
		
		// keep track of node entrance in the token, so that a node-log can be generated at node leave time.
		$token->setNodeEnter(new \DateTime());
		
		// remove the transition references from the runtime context
		$executionContext->setTransition(null);
		$executionContext->setTransitionSource(null);
		
		// execute the node
		if ($this->isAsync) {
			// Message continuationMsg = new ExecuteNodeCommand(this, executionContext.getToken());
			// MessageService messageService = (MessageService) Services.getCurrentService(Services.SERVICENAME_MESSAGE);
			// messageService.send(continuationMsg);
			throw new \Exception("Someone should teach me about async execution");
		} else {
			$this->execute($executionContext);
		}
	}
	
	/**
	 * override this method to customize the node behaviour.
	 */
	public function execute(ExecutionContext $executionContext) {
		$this->log->debug("XXX execute [{$this}]");
		// if there is a custom action associated with this node
		if (!is_null($this->action)) {
			try {
				// execute the action
				$this->action->execute($executionContext);
			} catch ( \Exception $exception ) {
				// NOTE that Error's are not caught because that might halt the JVM and mask the original Error.
				// search for an exception handler or throw to the client
				$this->raiseException($exception, $executionContext);
			}
		} else {
			// let this node handle the token
			// the default behaviour is to leave the node over the default transition.
			$this->leave($executionContext);
		}
	}
	
	// /**
	// * called by the implementation of this node to continue execution over the default transition.
	// */
	// public void leave(ExecutionContext executionContext) {
	// leave(executionContext, getDefaultLeavingTransition());
	// }
	
	/**
	 * called by the implementation of this node to continue execution over the specified transition.
	 */
	public function leave(ExecutionContext $executionContext, $transitionNameOrTransition = "") {
		$this->log->debug("EXEC leaveing node [{$this}]. transitionNameOrTransition: [{$transitionNameOrTransition}]");
		if ($transitionNameOrTransition instanceof Transition) {
			/**@var Transition**/
			$transition = $transitionNameOrTransition;
		} else {
			if ($transitionNameOrTransition == "" || is_null($transitionNameOrTransition)) {
				$transition = $this->getDefaultLeavingTransition();
			} else {
				$transition = $this->getLeavingTransition($transitionNameOrTransition);
			}
			if (is_null($transition)) {
				throw new \Exception("transition '[{$transitionNameOrTransition}]' is not a leaving transition of node [{$this}]");
			}
		}
		
		$token = $executionContext->getToken();
		$token->setNode($this);
		$executionContext->setTransition($transition);
		
		$this->fireEvent(Event::EVENTTYPE_NODE_LEAVE, $executionContext);
		if (!is_null($token->getNodeEnter())) {
			$this->addNodeLog($token);
		}
		$executionContext->setTransitionSource($this);
		$transition->take($executionContext);
	}
	protected function addNodeLog(Token $token) {
		$ne = $token->getNodeEnter()->format('Y-m-d H:i:s');
		$this->log->info("XXX nodeLog: [{$this->getName()}], [{$ne}], []");
		// $this->token->addLog(new NodeLog($this, $token->getNodeEnter(), new \DateTime()));
	}
	
	// ///////////////////////////////////////////////////////////////////////////
	public function getProcessDefinition() {
		$pd = $this->processDefinition;
		if (!is_null($this->superState)) {
			$pd = $this->superState->getProcessDefinition();
		}
		return $pd;
	}
	
	// // change the name of a node ////////////////////////////////////////////////
	/**
	 * updates the name of this node
	 */
	public function setName($name) {
		if ($this->name != $name) {
			if (!is_null($this->superState)) {
				if ($this->superState->hasNode($name)) {
					throw new \Exception("couldn't set name '{$name}' on node '{$this}' cause the superState of this node has already another child node with the same name");
				}
				$this->superState->nodes->removeElement($this);
				$this->name = $name;
				$this->superState->addNode($this);
			} elseif (!is_null($this->processDefinition)) {
				if ($this->processDefinition->hasNode($name)) {
					throw new \Exception("couldn't set name '{$name}' on node '{$this}' cause the process definition of this node has already another node with the same name");
				}
				// Java had this directly manipulating the node map, which was returned as a reference (as all obj's are)
				// But fuck trying to deal with array references.
				$this->processDefinition->updateNodeName($this, $name);
				// $this->processDefinition->addNode($this);
			}
			$this->name = $name;
		}
	}
	
	// boolean isDifferent(String name1, String name2) {
	// if ((name1!=null)
	// && (name1.equals(name2))) {
	// return false;
	// } else if ( (name1==null)
	// && (name2==null) ) {
	// return false;
	// }
	// return true;
	// }
	
	/**
	 * the slash separated name that includes all the superstate names.
	 *
	 * @todo superstate
	 */
	public function getFullyQualifiedName() {
		$fullyQualifiedName = $this->name;
		if (!is_null($this->superState)) {
			$fullyQualifiedName = $this->superState->getFullyQualifiedName() . "/" . $this->name;
		}
		return $fullyQualifiedName;
	}
	
	// // getters and setters //////////////////////////////////////////////////////
	
	/**
	 *
	 * @return SuperState
	 */
	public function getSuperState() {
		return $this->superState;
	}
	/**
	 *
	 * @return Action
	 */
	public function getAction() {
		return $this->action;
	}
	public function setAction(Action $action) {
		$this->action = $action;
	}
	public function isAsync() {
		return $this->isAsync;
	}
	/**
	 *
	 * @param boolean $isAsync        	
	 */
	public function setAsync($isAsync) {
		$this->isAsync = boolval($isAsync);
	}
	public static function getNodeType($type) {
		if (array_key_exists($type, self::$nodeTypes)) {
			return static::nodeTypes($type);
		} else {
			return null;
		}
	}
	public static function getDomNodeTypeFromClass($class) {
		$classToNodes = array_flip(self::$nodeTypes);
		if (array_key_exists($class, $classToNodes)) {
			return $classToNodes[$class];
		} else {
			return null;
		}
	}
}
