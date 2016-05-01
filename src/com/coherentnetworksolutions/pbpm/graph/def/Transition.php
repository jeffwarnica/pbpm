<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;
use Doctrine\Common\Collections\ArrayCollection;

/** @Entity **/
class Transition extends GraphElement {
    /**
     * @var Node
     */
    public $from = null;
    
    /**
     * @var Node
     */
    public $to = null;
    
    /**
     * @var string $condition
     */
    protected $condition;
    
    /**
     * @var bool
     */
    protected $isConditionEnforced = true;
    
    
    public static $supportedEventTypes = [
        Event::EVENTTYPE_TRANSITION,
    ];
    
    public function __construct($name = null) {
        parent::__construct($name);
    }
    
    public function getSupportedEventTypes() {
        return self::$supportedEventTypes;
    }
    
    /**
     * @return Node
     */
    public function getFrom() {
        return $this->from;
    }
    
    /**
     * sets the from node unidirectionally.  use {@link Node#addLeavingTransition(Transition)}
     * to get bidirectional relations mgmt.
     */
    public function setFrom(Node $from) {
        $this->from = $from;
    }
    
    
    /**
     * sets the to node unidirectionally.  use {@link Node#addArrivingTransition(Transition)}
     * to get bidirectional relations mgmt.
     */
    public function setTo(Node $to) {
        $this->to = $to;
    }
    
    /**
     * @return Node
     */
    public function getTo() {
        return $this->to;
    }
    
    /**
     * the condition expression for this transition.
     * @return string condition
     */
    public function getCondition() {
    	return $this->condition;
    }
    
    public function setCondition($conditionExpression) {
    	$this->condition = $conditionExpression;
    }
    
    /**
     * @return bools
     */
    public function isConditionEnforced() {
    	return $this->isConditionEnforced;
    }
    
    public function setConditionEnforced($conditionEnforced) {
    	$this->isConditionEnforced = $conditionEnforced;
    }
    
    /**
     * passes execution over this transition.
     */
    public function take(ExecutionContext $executionContext) {
    	$this->log->debug("EXEC take [{$this}]");
        // update the runtime context information
        $executionContext->getToken()->setNode(null);
    
        $token = $executionContext->getToken();
    
        // start the transition log
//         $transitionLog = new TransitionLog($this, $executionContext->getTransitionSource());
//         token.startCompositeLog(transitionLog);
        try {
    
            // fire leave events for superstates (if any)
            $this->fireSuperStateLeaveEvents($executionContext);
    
            // fire the transition event (if any)
            $this->fireEvent(Event::EVENTTYPE_TRANSITION, $executionContext);
    
            // fire enter events for superstates (if any)
            $destination = $this->fireSuperStateEnterEvents($executionContext);
            // update the ultimate destinationNode of this transition
//             $transitionLog->setDestinationNode($destination);
    
        } finally {
            // end the transition log
//             token.endCompositeLog();
        }
    
        // pass the token to the destinationNode node
        $this->to->enter($executionContext);
    }
    
    /**
     * @return Node 
     */
    private function fireSuperStateEnterEvents(ExecutionContext $executionContext) {
        // calculate the actual destinationNode node
        $destination = $this->to;
        while ($destination instanceof SuperState) {
            $destination = $destination->getNodes()[0];
        }
    
        if (is_null($destination)) {
            $transitionName = (!is_null($this->name) ? "'{$this->name}'" : "in node '{$this->from}'");
            throw new \Exception("transition {$transitionName} doesn't have destination. check your processdefinition.xml");
        }
    
        // performance optimisation: check if at least there is a candidate superstate to be entered.
        if ( !is_null(!$destination->getSuperState()) ) {
            // collect all the superstates being left
            $leavingSuperStates = $this->collectAllSuperStates($destination, $this->from);
            // reverse the order so that events are fired from outer to inner superstates
            $revLeavingSuperStates = new ArrayCollection();
        	for($i=$leavingSuperStates->count(); $i>0; $i--){
				$revLeavingSuperStates->add($leavingSuperStates->get($i));
			}
            // fire a superstate-enter event for all superstates being left
            $this->fireSuperStateEvents($revLeavingSuperStates, Event::EVENTTYPE_SUPERSTATE_ENTER, $executionContext);
        }
    
        return $destination;
    }
    
    private function fireSuperStateLeaveEvents(ExecutionContext $executionContext) {
        // performance optimisation: check if at least there is a candidate superstate to be left.
        if (!is_null($executionContext->getTransitionSource()->getSuperState())) {
            // collect all the superstates being left
            $leavingSuperStates = $this->collectAllSuperStates($executionContext->getTransitionSource(), $this->to);
            // fire a node-leave event for all superstates being left
            $this->fireSuperStateEvents($leavingSuperStates, Event::EVENTTYPE_SUPERSTATE_LEAVE, $executionContext);
        }
    }
    
    /**
     * collect all superstates of a that do not contain node b.
     * @return ArrayCollection
     */
    static function collectAllSuperStates(Node $a, Node $b) {
        $superState = $a->getSuperState();
        $leavingSuperStates = new ArrayCollection();
        while (!is_null($superState)) {
            if (!$superState->containsNode($b)) {
                $leavingSuperStates->add($superState);
                $superState = $superState->getSuperState();
            } else {
                $superState = null;
            }
        }
        return $leavingSuperStates;
    }
    
    /**
     * fires the give event on all the superstates in the list.
     */
    private function fireSuperStateEvents($superStates = array(), $eventType, ExecutionContext $executionContext) {
    	$this->log->debug("EXEC fireSuperStateEvents (size of superStates is: [" . sizeof($superStates) . "])");
        foreach ($superStates as $leavingSuperState) {
        	if ($leavingSuperState instanceof GraphElement){
	        	/**@var SuperState $leavingSuperState) **/
	            $leavingSuperState->fireEvent($eventType, $executionContext);
        	}
        }
    }
    
    // other
    /////////////////////////////////////////////////////////////////////////////
    //NOTE: gLTM() needs to return an object, eg. ArrayCollection, not a straight array, because references
//     public function setName($name) {
//         if (!is_null($this->from)) {
//             if ( $this->from->hasLeavingTransition($name) ) {
//                 throw new IllegalArgumentException("couldn't set name '{$name}' on transition '{$this}'cause the from-node of this transition has already another leaving transition with the same name");
//             }
//             $fromLeavingTransitions = $this->from->getLeavingTransitionsMap();
//             $fromLeavingTransitions->(this.name);
//             $fromLeavingTransitions.put(name,this);
//         }
//         this.name = name;
//     }
    
    /**
     * @return GraphElement
     * @see \com\coherentnetworksolutions\pbpm\graph\def\GraphElement::getParent()
     */
    public function getParent() {
    	$this->log->debug(">getParent()");
    	$this->log->debug("I AM [{$this->getName()}]. I go [{$this->from->getName()}] --> [{$this->to->getName()}]");
    	$_parent = parent::getParent();
    	
    	$this->log->debug("Stupid mode. I WOULD return parent::getParent()->getName() is [{$_parent->getName()}]");

    	$parent = null;
    	if (!is_null($this->from) && !is_null($this->to)) {
    		$this->log->debug("From and too are not null");
    		if ($this->from == $this->to) {
    			$this->log->debug("From==To, so parent must be From");
    			$parent = $this->from->getParent();
    		} else {
    			$this->log->debug("From != To (normal case). Doing complex shit");
    			$fromParentChain = $this->from->getParentChain();
    			$toParentChain = $this->to->getParentChain();
    			$fromIter = $fromParentChain->getIterator();
    			while ($fromIter->valid() && is_null($parent)) {
    				/** @var Node **/
    				$fromParent = $fromIter->current();
    				$toIter = $toParentChain->getIterator();
    				while ($toIter->valid() && is_null($parent)) {
    					/** @var Node **/
    					$toParent = $toIter->current();
	    				$this->log->debug("\tfromParent: {$fromParent->getName()} / toParent: {$toParent->getName()}");
    					if ($fromParent == $toParent) {
    						$parent = $fromParent;
    					}
    					$toParent = $toIter->next();
    				}
	    			$fromIter->next();
    			}
    		}
    	}
    	$this->log->debug("ABOUT TO RETURN. Returning [" . (is_null($parent) ? "NULL" : $parent->getName())  . "]");
    	return $parent;
    	
    }
    
    public function __toString() {
    	$name = is_null($this->name) ? "<unnamed>" : $this->name;
    	$from = is_null($this->from) ? "<unknown>" : $this->from->getName();
    	$to = is_null($this->to) ? "<unknown>" : $this->to->getName();
    	return "Transition[{$name}] [{$from}]-->[$to]";
    }
    
}