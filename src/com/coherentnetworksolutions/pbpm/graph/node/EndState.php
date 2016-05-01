<?php
namespace com\coherentnetworksolutions\pbpm\graph\node;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;

/**
 * @entity
 */
class EndState extends Node {

    public static $supportedEventTypes = [
        Event::EVENTTYPE_NODE_ENTER,
    ];
    
    public function __construct($name = null) {
        parent::__construct($name);
    }

    public function execute(ExecutionContext $executionContext) {
    	$this->log->debug("XXX execute [{$this}]");
        $executionContext->getToken()->end();
    }

    /**
     * @return Transition 
     * @see \com\coherentnetworksolutions\pbpm\graph\def\Node::addLeavingTransition()
     */
    public function addLeavingTransition(Transition $t = null) {
        throw new \Exception("can't add a leaving transition to an end-state");
    }
}
