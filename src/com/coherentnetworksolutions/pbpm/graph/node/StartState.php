<?php
namespace com\coherentnetworksolutions\pbpm\graph\node;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;

/**
 * @entity
 */
class StartState extends Node {

    public static $supportedEventTypes = [
        Event::EVENTTYPE_NODE_LEAVE,
        Event::EVENTTYPE_AFTER_SIGNAL
    ];

    public function __construct($name = null) {
        parent::__construct($name);
    }

//     // xml //////////////////////////////////////////////////////////////////////

    public function read(\DOMElement $startStateElement, JpdlXmlReader $jpdlReader) {
        parent::read($startStateElement, $jpdlReader);
        // if the start-state has a task specified,
        $startTaskElements = $startStateElement->getElementsByTagName("task");
        if (sizeof($startTaskElements) == 1) {
            $startTaskElement = $startTaskElements->item(0);
        }
        if ($startTaskElement!=null) {
            // delegate the parsing of the start-state task to the jpdlReader
            $jpdlReader->readStartStateTask($startTaskElement, $this);
        }
    }

    public function write(\DOMElement $nodeElement) {
    }

    public function leave(ExecutionContext $executionContext, $transitionNameOrTransition = "") {
    	// leave this node as usual
        parent::leave($executionContext, $transitionNameOrTransition);
    }

    public function execute(ExecutionContext $executionContext) {
    	//Do SFA. Intentionally.
    }

//     public Transition addArrivingTransition(Transition t) {
//         throw new UnsupportedOperationException( "illegal operation : its not possible to add a transition that is arriving in a start state" );
//     }

//     public void setArrivingTransitions(Map arrivingTransitions) {
//         if ( (arrivingTransitions!=null)
//                         && (arrivingTransitions.size()>0)) {
//                             throw new UnsupportedOperationException( "illegal operation : its not possible to set a non-empty map in the arriving transitions of a start state" );
//                         }
//     }
}
