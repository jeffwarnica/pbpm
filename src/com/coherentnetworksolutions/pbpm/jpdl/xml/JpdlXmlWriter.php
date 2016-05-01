<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;

use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\node\StartState;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\jpdl\JpdlException;
use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\graph\def\GraphElement;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
/**
 * @deprecated xml generation was never finished and will be removed in the future.
 * jwarnica: (BUT NECESSARY FOR TESTS (*sigh*)
 */
class JpdlXmlWriter {

    const JPDL_NAMESPACE = "http://jbpm.org/3/jpdl";

    private $problems = array();
    private $useNamespace = false;
    /**
     * 
     * @var \Logger
     */
    private $log;
    
    public function __construct() {
        $this->log = \Logger::getLogger(__CLASS__);
    }
    
    public function addProblem($msg) {
        $this->problems[] = $msg;
    }
    public static function ProcessDefinitionToString(ProcessDefinition $processDefinition) {
        $jpdlWriter = new JpdlXmlWriter();
        return $jpdlWriter->write($processDefinition);
    }

    public function setUseNamespace($useNamespace) {
        $this->useNamespace = $useNamespace;
    }

    //newElement.add( jbpmNamespace );

    /**
     * 
     * @param ProcessDefinition $processDefinition
     * @throws JpdlException
     * @return \DOMDocument
     */
    public function write(ProcessDefinition $processDefinition) {
        try {
            // collect the actions of the process definition
            // we will remove each named event action and the remaining ones will be written
            // on the process definition.
            // create a dom4j dom-tree for the process definition
            $document = $this->createDomTree($processDefinition);

            
        } catch (Exception $e) {
            print $e->getTrace();
            $this->addProblem("couldn't write process definition xml: " . $e->getMessage());
        }

        if (sizeof($this->problems)>0) {
            throw new JpdlException($this->problems);
        }
        return $document;
    }

    private function createDomTree(ProcessDefinition $processDefinition) {
        
        $document = new \DOMDocument();

        if ($this->useNamespace) {
            $root = new \DOMElement("process-definition", null, self::JPDL_NAMESPACE);
        } else {
            $root = new \DOMElement("process-definition");
        }
        $document->appendChild($root);
        
        $this->addAttribute( $root, "name", $processDefinition->getName() );

        // write the start-state
        if (!is_null($processDefinition->getStartState()) ) {
            $this->writeComment($root, "START-STATE");
            $this->writeStartNode($root, $processDefinition->getStartState() );
        }
//         // write the nodeMap
        if ( sizeof($processDefinition->getNodes() )) { 
            $this->writeComment($root, "NODES");
            $this->writeNodes( $root, $processDefinition->getNodes() );
        }
        // write the process level actions
        if ( $processDefinition->hasEvents() ) {
            $this->writeComment($root, "PROCESS-EVENTS");
            $this->writeEvents( $root, $processDefinition );
        }
        
        if( $processDefinition->hasActions() ) {
            $this->writeComment($root, "ACTIONS");
            $namedProcessActions = $this->getNamedProcessActions($processDefinition->getActions());
            $this->writeActions($root, $namedProcessActions);
        }

        $this->writeComment($root, "\nEND OF DOC");

        return $document;
    }

    private function getNamedProcessActions(ArrayCollection $actions) {
        $namedProcessActions = [];
        foreach ($actions as $action) {
            $this->log->debug("getNamedProcessActions: possible action: {$action->getEvent()} {$action->getName()}");
            $this->log->debug(print_r($action->getEvent(), true));  
            if ( is_null($action->getEvent()) && $action->getName()!="" ) {
                $this->log->debug("\t added!");
                $namedProcessActions[] = $action;
            }
        }
        return $namedProcessActions;
    }

    private function writeStartNode(\DOMElement $element, StartState $startState = null) {
        if (!is_null($startState)) {
            $this->writeNode( $this->addElement( $element, $this->getTypeName($startState) ), $startState );
        }
    }

    private function writeNodes(\DOMElement $parentElement, ArrayCollection $nodes) {
        foreach ($nodes as  $node) {
            if ( ! ($node instanceof StartState) ) {
                $nodeElement = $this->addElement( $parentElement, $this->getTypeName($node) );
                $node->write($nodeElement);
                $this->writeNode( $nodeElement, $node );
            }
        }
    }

    private function writeNode(\DOMElement $element, Node $node ) {
        $this->addAttribute( $element, "name", $node->getName() );
        $this->writeTransitions($element, $node);
        $this->writeEvents($element, $node);
    }

    private function writeTransitions(\DOMElement $element, Node $node) {
        $leavingTransitions = $node->getLeavingTransitions();
        foreach ($leavingTransitions as $transition) {
            $tElement = new \DOMElement("transition");
            $element->appendChild($tElement);
            $this->writeTransition( $tElement, $transition);
        }
    }

    private function writeTransition(\DOMElement $transitionElement, Transition $transition) {
        if (!is_null($transition->getTo())) {
            $this->addAttribute($transitionElement, "to", $transition->getTo()->getName());
        }
        if ( !is_null($transition->getName())) {
            $this->addAttribute($transitionElement, "name", $transition->getName());
        }
        $transitionEvent = $transition->getEvent(Event::EVENTTYPE_TRANSITION);
        if ( (!is_null($transitionEvent))
                        && ($transitionEvent->hasActions()) ){
            $this->writeActions($transitionElement, $transitionEvent->getActions());
        }
    }

    private function writeEvents(\DOMElement $element, GraphElement $graphElement) {
        if ($graphElement->hasEvents()) {
            foreach ($graphElement->getEvents() as $event) {
                $eventElem = new \DOMElement("event");
                $element->appendChild($eventElem);
                $this->writeEvent( $eventElem, $event );
            }
        }
    }

    private function writeEvent(\DOMElement $eventElement, Event $event) {
        $this->addAttribute($eventElement, "type", $event->getEventType());
        if ($event->hasActions()) {
            foreach ($event->getActions() as $action) {
                $this->writeAction($eventElement, $action);
            }
        }
    }

    private function writeActions(\DOMElement $parentElement, $actions = array()) {
        $this->log->debug(">writeActions()");
        foreach($actions as $action) {
            $this->writeAction( $parentElement, $action );
        }
    }

    private function writeAction(\DOMElement $parentElement, Action $action ) {
        $this->log->debug(">writeAction()");
        $actionTagName = Action::getActionName(get_class($action));
        $actionElement = new \DOMElement($actionTagName); 
        $parentElement->appendChild($actionElement);

        $this->log->debug("\tactionTagName: $actionTagName");
        if ($action->getName()!="") {
            $this->addAttribute($actionElement, "name", $action->getName());
            $this->log->debug("\tactionName: [{$action->getName()}]");
        }

        if (!$action->acceptsPropagatedEvents()) {
            $this->addAttribute($actionElement, "accept-propagated-events", "false");
        }

        $action->write($actionElement);
    }

    private function writeComment(\DOMElement $element, $comment ) {
        $comment = new \DOMComment($comment);
        $element->appendChild($comment);
    }

    private function addElement( \DOMElement $element, $elementName ) {
        $newElement = new \DOMElement($elementName); 
        $element->appendChild($newElement);
        return $newElement;
    }

    private function addAttribute( \DOMElement $e, $attributeName, $value ) {
        if ( $value != "" ) {
            $attr = new \DOMAttr($attributeName, $value);
            $e->appendChild($attr);
        }
    }

    private function getTypeName( $o ) {
        return Node::getDomNodeTypeFromClass(get_class($o));
    }

    // private static final Log log = LogFactory.getLog(JpdlXmlWriter.class);
}