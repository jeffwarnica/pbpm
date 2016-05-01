<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\jpdl\JpdlException;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\NodeCollection;
use com\coherentnetworksolutions\pbpm\graph\def\GraphElement;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;
use com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskMgmtDefinition;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Task;
use com\coherentnetworksolutions\pbpm\graph\node\TaskNode;
use com\coherentnetworksolutions\pbpm\graph\node\StartState;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskController;
use com\coherentnetworksolutions\pbpm\context\def\VariableAccess;
use com\coherentnetworksolutions\pbpm\scheduler\def\CreateTimerAction;
use com\coherentnetworksolutions\pbpm\scheduler\def\CancelTimerAction;
use com\coherentnetworksolutions\pbpm\instantiation\Delegation;
use com\coherentnetworksolutions\pbpm\graph\def\ExceptionHandler;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Swimlane;
use Doctrine\Common\Collections\ArrayCollection;

class JpdlXmlReader {

    /**
     * @var string
     */
    protected $inputSource = null;

    public $problemsErr = array();
    public $problemsWarn = array();
    //     protected ProblemListener problemListener = null;
    /**
     * @var ProcessDefinition
     */
    protected $processDefinition = null;

    protected $unresolvedTransitionDestinations = array();

    protected $unresolvedActionReferences = array();

    private $initialNodeName;
    /**
     * 
     * @var \Logger
     */
    protected $log;

    public function __construct($inputSource) {
        $this->inputSource = $inputSource;
        $this->log = \Logger::getLogger(__CLASS__);
        $this->log->debug("__construct");
    }

    /**
     * @return ProcessDefinition
     */
    public function readProcessDefinition() {
        // create a new definition
        $this->processDefinition = ProcessDefinition::createNewProcessDefinition();
        
        try {
            $document = XmlLoader($this->inputSource);
            
            /**
             * @var \DOMElement
             */
            $root = $document->documentElement;
            
            // read the process name
            $this->parseProcessDefinitionAttributes($root);
            
            // get the process description
            $this->processDefinition->setDescription($this->getDescription($root));
            
            // first pass: read most content
// $this->log->debug("TOP LEVEL >readSwimlanes()");            
            $this->readSwimlanes($root);
// $this->log->debug("TOP LEVEL <readSwimlanes()");            
$this->log->debug("TOP LEVEL >readActions()");            
            $this->readActions($root, null, null);
$this->log->debug("TOP LEVEL <readActions()");            
$this->log->debug("TOP LEVEL >readNodes()");
            $this->readNodes($root, $this->processDefinition);
$this->log->debug("TOP LEVEL <readNodes()");
$this->log->debug("TOP LEVEL >readEvents()");
			$this->readEvents($root, $this->processDefinition);
$this->log->debug("TOP LEVEL <readEvents()");            
$this->log->debug("TOP LEVEL >readExceptionHandlers()");
            $this->readExceptionHandlers($root, $this->processDefinition);
$this->log->debug("TOP LEVEL <readExceptionHandlers()");
$this->log->debug("TOP LEVEL >readTasks()");                                  
            $this->readTasks($root, null);
$this->log->debug("TOP LEVEL <readTasks()");
$this->log->debug("TOP LEVEL >resolveAllTransitionDestinations()");            
            //             // second pass processing
            $this->resolveAllTransitionDestinations();
$this->log->debug("TOP LEVEL <resolveAllTransitionDestinations()");
$this->log->debug("TOP LEVEL >resolveActionReferences()");                        
            $this->resolveActionReferences();
$this->log->debug("TOP LEVEL <resolveActionReferences()");
$this->log->debug("TOP LEVEL >verifySwimlaneAssignments()");
            $this->verifySwimlaneAssignments();
        } catch ( \Exception $e ) {
            $this->log->error("Couldn't parse process definition", $e);
            $this->problemsErr[] = $e;
        }
        
        if ( sizeof($this->problemsErr) > 0 ) {
            throw new JpdlException(join(", ", $this->problemsErr));
        }
        
        return $this->processDefinition;
    }
    
    private function getDescription(\DOMNode $node) {
    	$descNodes = $node->getElementsByTagName("description");
    	if ($descNodes->length>0) {
    		return $descNodes->item(0)->textContent;
    	}
    }

    public function readNodes(\DOMElement $element, NodeCollection $nodeCollection) {
        $this->log->debug(">readNodes([] [{$nodeCollection->getName()}])");
        $children = $element->childNodes;
        
        foreach ( $children as $child ) {
            if (!$child->parentNode->isSameNode($element) || $child->nodeName === "#text" ) {
                continue;
            }
            
            $this->log->debug("CREATING NODE OF TYPE {$child->nodeName}, name: [{$child->getAttribute("name")}] (from line #: [{$child->getLineNo()}])");
            
            $clazz = array_key_exists($child->nodeName, Node::$nodeTypes) ? Node::$nodeTypes[$child->nodeName] : null;
            
            if ( !is_null($clazz) ) {
				$this->log->debug("going to new up a [$clazz]");
                /** @var Node*/
                $node = new $clazz();
                
                $node->setProcessDefinition($this->processDefinition);
                
                if ( $node instanceof StartState && !is_null($this->processDefinition->getStartState()) ) {
                    $this->addError("max one start-state allowed in a process");
                } else {
                    $this->readNode($child, $node, $nodeCollection);
                    $node->read($child, $this);
                }
            } else {
                $this->addWarning("Unknown node type found: [{$child->nodeName}]");
            }
        }
        $this->log->debug("<readNodes()");
    }
    
    protected function readSwimlanes(\DOMElement $processDefinitionElement) {
    	$taskMgmtDefinition = $this->processDefinition->getTaskMgmtDefinition();
    	$children = $processDefinitionElement->childNodes;
    	foreach ( $children as $swimlaneElement ) {
    		/** @var $swimlaneElement \DOMElement **/
    		if ($swimlaneElement->nodeName !== "swimlane" ) {
    			continue;
    		}
    		$swimlaneName = $swimlaneElement->getAttribute("name");
    		if ($swimlaneName == "") {
    			$this->addWarning("unnamed swimlane detected");
    		} else {
    			$swimlane = new Swimlane($swimlaneName);
    			/**
    			 * @var \DOMElement $assignmentElement
    			 */
    			$assignmentElement = $swimlaneElement->getElementsByTagName("assignment")[0];
    
    			if (!is_null($assignmentElement)) {
    				if ($assignmentElement->getAttribute("actor-id") != "" 
    						|| $assignmentElement->getAttribute("pooled-actor") != "") {
    					$swimlane->setActorIdExpression($assignmentElement->getAttribute("actor-id"));
    					$swimlane->setPooledActorsExpression($assignmentElement->getAttribute("pooled-actors"));
    				} else {
    					$assignmentDelegation = $this->readAssignmentDelegation($assignmentElement);
    					$swimlane->setAssignmentDelegation($assignmentDelegation);
    				}
    			} else {
    				$startTask = $taskMgmtDefinition->getStartTask();
    				if (is_null($startTask) || $startTask->getSwimlane() != $swimlane) {
    					$this->addWarning("swimlane {$swimlaneName} does not have an assignment");
    				}
    			}
    			$taskMgmtDefinition->addSwimlane($swimlane);
    		}
    	}
    }

    public function readStartStateTask(\DOMElement $startTaskElement, StartState $startState) {
        $taskMgmtDefinition = $this->processDefinition->getTaskMgmtDefinition();
        $startTask = $this->readTask($startTaskElement, $taskMgmtDefinition, null);
        $startTask->setStartState($startState);
        if ( $startTask->getName() == "" ) {
            $startTask->setName($startState->getName());
        }
        $taskMgmtDefinition->setStartTask($startTask);
    }

    public function readNode(\DOMElement $nodeElement, Node $node, NodeCollection $nodeCollection) {
        $this->log->debug(">readNode({$nodeElement->nodeName})");
        // add the node to the parent
        $nodeCollection->addNode($node);
        $node->setDescription($this->getDescription($nodeElement));
        
        $name=$nodeElement->getAttribute("name");
        
        if ($name != ""){
        	$node->setName($name);
        	if ($this->initialNodeName != "" && $this->initialNodeName == $node->getFullyQualifiedName()) {
        		$this->processDefinition->setStartState($node);
        	}
        }
        
        if ( "true" === $nodeElement->getAttribute("async") ) {
            $node->setAsync(true);
        }
        
        // parse common subelements
        $this->readNodeTimers($nodeElement, $node);
        $this->readEvents($nodeElement, $node);
        $this->readExceptionHandlers($nodeElement, $node);

        // save the transitions and parse them at the end
        $this->addUnresolvedTransitionDestination($nodeElement, $node);
        $this->log->debug("<readNode({$nodeElement->nodeName})");
    }

    protected function readNodeTimers(\DOMElement $nodeElement, Node $node) {
        foreach ( $nodeElement->getElementsByTagName("timer") as $timerElement ) {
            $this->readNodeTimer($timerElement, $node);
        }
    }

    protected function readNodeTimer(\DOMElement $timerElement, Node $node) {
        $name = $timerElement->getAttribute("name");
        if ( $name == "" ) {
            $name = $node->getName();
        }
        
        $createTimerAction = new CreateTimerAction();
        $createTimerAction->read($timerElement, $this);
        $createTimerAction->setTimerName($name);
        $createTimerAction->setTimerAction($this->readSingleAction($timerElement));
        $this->addAction($node, Event::EVENTTYPE_NODE_ENTER, $createTimerAction);
        
        $cancelTimerAction = new CancelTimerAction();
        $cancelTimerAction->setTimerName($name);
        $this->addAction($node, Event::EVENTTYPE_NODE_LEAVE, $cancelTimerAction);
    }

    public function readTasks(\DOMElement $element, TaskNode $taskNode = null) {
    	$allTaskNodes = $this->getDirectChildrenByTagName($element, "task");

    	/**
         * @var TaskMgmtDefinition
         */
        $tmd = $this->processDefinition->getDefinition('com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskMgmtDefinition');
        
        if ( sizeof($allTaskNodes) > 0 ) {
            if ( is_null($tmd) ) {
                $tmd = new TaskMgmtDefinition();
                $this->processDefinition->addDefinition($tmd);
            }
            
            foreach ( $allTaskNodes as $childNode ) {
                $this->readTask($childNode, $tmd, $taskNode);
            }
        }
    }

    /**
     * @return Task
     */
    public function readTask(\DOMElement $taskElement, TaskMgmtDefinition $taskMgmtDefinition, TaskNode $taskNode = null) {
    	$this->log->debug("\t>readTask()");
        /**
         * @var Task
         */
        $task = new Task();
        $task->setProcessDefinition($this->processDefinition);
        
        // get the task name
        $name = $taskElement->getAttribute("name");
        if ( !is_null($name) ) {
            $task->setName($name);
            $taskMgmtDefinition->addTask($task);
        } else if ( !is_null($taskNode) ) {
            $task->setName($taskNode->getName());
            $taskMgmtDefinition->addTask($task);
        }
        
        // parse common subelements
        $this->readTaskTimers($taskElement, $task);
        $this->readEvents($taskElement, $task);
        $this->readExceptionHandlers($taskElement, $task);

        // description and duration
        $task->setDescription($this->getDescription($taskElement));
        $duedateText = $taskElement->getAttribute("duedate");
        if ( $duedateText == "" ) {
            $duedateText = $taskElement->getAttribute("dueDate");
        }
        $task->setDueDate($duedateText);
        $priorityText = $taskElement->getAttribute("priority");
        if ( is_null($priorityText) ) {
            $task->setPriority(Task::parsePriority($priorityText));
        }
        
        // if this task is in the context of a taskNode, associate them
        if ( !is_null($taskNode) ) {
            $taskNode->addTask($task);
        }
        
        // blocking
        $blockingText = $taskElement->getAttribute("blocking");
        if ( $blockingText != "" ) {
            $blockingText = strtolower($blockingText);
            if ( ("true" == $blockingText) || ("yes" == $blockingText) || ("on" == blockingText) ) {
                $task->setBlocking(true);
            }
        }
        
        // signalling
        $signallingText = $taskElement->getAttribute("signalling");
        if ( $signallingText != "" ) {
            $signallingText = strtolower($signallingText);
            if ( ("false" == $signallingText) || ("no" == $signallingText) || ("off" == $signallingText) ) {
                $task->setSignalling(false);
            }
        }
              
		// assignment
		$swimlaneName = $taskElement->getAttribute("swimlane");
		$assignmentElement = $taskElement->getElementsByTagName("assignment")[0];

        // if there is a swimlane attribute specified
        if ($swimlaneName!="") {
        	$this->log->debug("\t>readTask() GOT A SWIMLANE: {$swimlaneName}");
        	$swimlane = $taskMgmtDefinition->getSwimlane($swimlaneName);
			if (is_null($swimlane)) {
				$this->log->debug("WTF, IDK about this swimlane {$swimlaneName}");
				$this->addWarning("task references unknown swimlane {$swimlaneName} " . $taskElement->getNodePath());
            } else {
            	$task->setSwimlane($swimlane);
            }
        

        // else if there is a direct assignment specified
        } else if (!is_null($assignmentElement)) {
        	if ( ($assignmentElement->getAttribute("actor-id")!="")
        		|| ($assignmentElement->getAttribute("pooled-actors")!="") ) {
        		$task->setActorIdExpression($assignmentElement->getAttribute("actor-id"));
				$task->setPooledActorsExpression($assignmentElement->getAttribute("pooled-actors"));
            } else {
				$assignmentDelegation = $this->readAssignmentDelegation($assignmentElement);
				$task->setAssignmentDelegation($assignmentDelegation);
            }
        

        // if no assignment or swimlane is specified
        } else {
        	// the user has to manage assignment manually, so we better warn him/her.
			$this->addWarning("warning: no swimlane or assignment specified for task '" . $taskElement->getNodePath() . "'");
        }
        

        // task controller
        $taskControllerElements = $taskElement->getElementsByTagName("controller");
        if ( $taskControllerElements->length == 1 ) {
            $task->setTaskController($this->readTaskController($taskControllerElements->item(0)));
        }
        $this->log->debug("< readTask() about to return task w/description: {$task->getDescription()}");
        return $task;
    }

    /**
     * @return Delegation
     */
    protected function readAssignmentDelegation(\DOMElement $assignmentElement) {
//     	Delegation 
    	$assignmentDelegation = new Delegation();
    	$assignmentDelegation->setProcessDefinition($this->processDefinition);
    
    	$domDoc = new \DOMDocument();
    	
    	$expression = $assignmentElement->getAttribute("expression");
    	if ($expression != "") {
    		// read assigment expression
    		$confElem = $domDoc->createElement("expresson", $expression);
    		$domDoc->appendChild($confElem);
    		//@TODO: Not a java class
    		$assignmentDelegation->setClassName("org.jbpm.identity.assignment.ExpressionAssignmentHandler");
    		$assignmentDelegation->setConfiguration($domDoc->saveXML());
    	} else {
    		$actorId = $assignmentElement->getAttribute("actor-id");
    		$pooledActors = $assignmentElement->getAttribute("pooled-actors");
    		if ($actorId != ""|| $pooledActors != "") {
    			// read assignment actors
    			$confElem = $domDoc->createElement("configuration");
    			if ($actorId != "") $childElem = $domDoc->createElement("actorId", $actorId);
    			if ($pooledActors != "") $childElem = $domDoc->createElement("pooledActors",pooledActors);
    			$confElem->appendChild($childElem);
    			$domDoc->appendChild($confElem);
    			
    			$assignmentDelegation->setClassName("org.jbpm.taskmgmt.assignment.ActorAssignmentHandler");
    			$assignmentDelegation->setConfiguration($domDoc->saveXML());
    		}
    		else {
    			// parse custom assignment handler
    			$assignmentDelegation->read($assignmentElement, $this);
    		}
    	}
    
    	return $assignmentDelegation;
    }
    
    
    /**
     * @return TaskController
     */
    protected function readTaskController(\DOMElement $taskControllerElement) {
        $taskController = new TaskController();
        
        if ( $taskControllerElement->getAttribute("class") != "" ) {
            $taskControllerDelegation = new Delegation();
            $taskControllerDelegation->read($taskControllerElement, $this);
            $taskController->setTaskControllerDelegation($taskControllerDelegation);
        } else {
            $variableAccesses = $this->readVariableAccesses($taskControllerElement);
            $taskController->setVariableAccesses($variableAccesses);
        }
        return $taskController;
    }

    /**
     * @param \DOMElement $element
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function readVariableAccesses(\DOMElement $element) {
        $variableAccesses = new ArrayCollection();
        
        $elements = $element->getElementsByTagName("variable");
        foreach ( $elements as $variableElement ) {
            
            $variableName = $variableElement->getAttribute("name");
            if ( $variableName == "" ) {
                //                 $this->addProblem(new Problem(Problem.LEVEL_WARNING, "the name attribute of a variable element is required: "+variableElement.asXML()));
            }
            $access = $variableElement->getAttribute("access");
            if ( $access == "" ) {
                $access = "read,write";
            }
            $mappedName = $variableElement->getAttribute("mapped-name");
            
            $variableAccesses->add(new VariableAccess($variableName, $access, $mappedName));
        }
        return $variableAccesses;
    }

    public function addUnresolvedTransitionDestination(\DOMElement $nodeElement, Node $node) {
        $this->unresolvedTransitionDestinations[] = [$nodeElement, $node];
    }

    protected function readTaskTimers(\DOMElement $taskElement, Task $task) {
        $timerNodes = $taskElement->getElementsByTagName("timer");
        foreach ( $timerNodes as $timerElement ) {
            $this->readTaskTimer($timerElement, $task);
        }
    }

    protected function readTaskTimer(\DOMElement $timerElement, Task $task) {
        $name = $timerElement->getAttribute("name");
        if ( $name == "" ) {
            $name = $task->getName();
        }
        ;
        if ( $name == "" ) {
            $name = "timer-for-task-" . $task->getId();
        }
        
        $createTimerAction = new CreateTimerAction();
        $createTimerAction->read($timerElement, $this);
        $createTimerAction->setTimerName($name);
        $createTimerAction->setTimerAction($this->readSingleAction($timerElement));
        $this->addAction($task, Event::EVENTTYPE_TASK_CREATE, $createTimerAction);
        
        $cancelEventTypes = array();
        
        $cancelEventTypeText = $timerElement->getAttribute("cancel-event");
        if ( $cancelEventTypeText != "" ) {
            // cancel-event is a comma separated list of events
            $bits = explode(",", $cancelEventTypeText);
            foreach ( $bits as $_ ) {
                $cancelEventTypes[] = trim($_);
            }
        } else {
            // set the default
            $cancelEventTypes[] = Event::EVENTTYPE_TASK_END;
        }
        
        foreach ( $cancelEventTypes as $cancelEventType ) {
            $cta = new CancelTimerAction();
            $cta->setTimerName($name);
            $this->addAction($task, $cancelEventType, $cta);
        }
    }

    protected function readEvents(\DOMElement $parentElement, GraphElement $graphElement) {
        $this->log->debug(">readEvents() {$parentElement->nodeName}/{$parentElement->getAttribute('name')}");
        foreach ($this->getDirectChildrenByTagName($parentElement, "event") as $eventElement) {
            
            $this->log->debug("\treadEvent: on Event element {$eventElement->getAttribute('name')}");
            /** @var $eventElement \DOMElement **/
            $eventType = $eventElement->getAttribute("type");
            if ( !$graphElement->hasEvent($eventType) ) {
                $this->log->debug("\treadEvent: \tEvent type exists, will create");
                $graphElement->addEvent(new Event($eventType));
            }
            $this->readActions($eventElement, $graphElement, $eventType);
        }
        $this->log->debug("<readEvents()");
    }

    public function readActions(\DOMElement $eventElement, GraphElement $graphElement = null, $eventType) {
        $this->log->debug(">readActions({$eventElement->nodeName} line no: [{$eventElement->getLineNo()}], " 
                        . ($graphElement ? $graphElement->getName() : "<>") . ", $eventType)");
        // for all the elements in the event element
        foreach ( $eventElement->childNodes as $possibleActionElement ) {
            $this->log->debug("\tGot possible ActionElemenet at line:" . $possibleActionElement->getLineNo());
            if (!$possibleActionElement->parentNode->isSameNode($eventElement)) {
                $this->log->debug("\t\tSkipping non-direct child node");
                continue;
            }
            
            /** @var $actionElement \DOMElement **/
            if ( Action::hasActionType($possibleActionElement->nodeName) ) {
                $this->log->debug("\t\t[{$possibleActionElement->nodeName}]/[{$possibleActionElement->getAttribute('class')}] seems to be an action");
                /** @var Action **/
                $action = $this->createAction($possibleActionElement);
                if ( !is_null($graphElement) && (!is_null($eventType)) ) {
                    // add the action to the event
                    $this->addAction($graphElement, $eventType, $action);
                }
            } else {
                $this->log->debug("\t\tUnable to add action from node type [{$possibleActionElement->nodeName}]");
            }
        }
        $this->log->debug("<readActions()");
    }

    protected function addAction(GraphElement $graphElement, $eventType, Action $action) {
        $event = $graphElement->getEvent($eventType);
        if ( is_null($event) ) {
            $event = new Event($eventType);
            $graphElement->addEvent($event);
        }
        $this->log->debug("Adding action type [{$eventType}] [{$action->getName()}]");
        $event->addAction($action);
    }

    public function readSingleAction(\DOMElement $nodeElement) {
        $this->log->debug(">readSingleAction() ");
        /**@var Action **/
        $action = null;
        foreach ( $nodeElement->childNodes as $candidate ) {
            if ( Action::hasActionType($candidate->nodeName) ) {
                $action = $this->createAction($candidate);
            } else {
                $this->log->debug("Unable to add action from node type [{$candidate->nodeName}]");
            }
        }
        $this->log->debug("<readSingleAction() ");
        return $action;
    }

    public function createAction(\DOMElement $actionElement) {

        $clazz = Action::getActionType($actionElement->nodeName);
        
        $this->log->debug(">createAction() from nodeName [{$actionElement->nodeName}] is clazz: [$clazz]");
        $action = new $clazz();
        if ( !($action instanceof Action) ) {
            throw new \Exception("could not create Action of class [$clazz] from type [{$actionElement->getAttribute("type")}]");
        }
        $this->readAction($actionElement, $action);
        
        return $action;
    }

    public function readAction(\DOMElement $element, Action $action) {
        $this->log->debug(">readAction({$element->getNodePath()})");
        // if a name is specified for this action
        $actionName = $element->getAttribute("name");
        $this->log->debug("\tactionName is: [{$actionName}]");
        if ( $actionName != "" ) {
            $action->setName($actionName);
            // add the action to the named process action repository
            $this->processDefinition->addAction($action);
        }
        
        // if the action is parsable
        // (meaning: if the action has special configuration to parse, other then the common node data)
        $action->read($element, $this);
    }

    protected function readExceptionHandlers(\DOMElement $graphElementElement, GraphElement $graphElement) {
        
        foreach ($graphElementElement->childNodes as $exceptionHandlerElement) {
            //skip #text and such
            if (!($exceptionHandlerElement instanceof \DOMElement)) {continue;} 
            if ($exceptionHandlerElement->nodeName !== "exception-handler") {continue;}
            $this->readExceptionHandler($exceptionHandlerElement, $graphElement);
        }
    }
    
    protected function readExceptionHandler(\DOMElement $exceptionHandlerElement, GraphElement $graphElement) {
        // create the exception handler
        $exceptionHandler = new ExceptionHandler();
        $exceptionHandler->setExceptionClassName($exceptionHandlerElement->getAttribute("exception-class"));
        // add it to the graph element
        $graphElement->addExceptionHandler($exceptionHandler);
        
        foreach ( $exceptionHandlerElement->childNodes as $possibleActionElement ) {
            /** @var $actionElement \DOMElement **/
            if ( Action::hasActionType($possibleActionElement->nodeName) ) {
                $action = $this->createAction($possibleActionElement);
                $exceptionHandler->addAction($action);
            }
        }
    }
    
    public function resolveAllTransitionDestinations() {
    	$this->log->debug(">resolveAllTransitionDestinations()");
        foreach ( $this->unresolvedTransitionDestinations as $_ ) {
        	$this->log->debug("\tWorking on uTD [{$_[1]->getName()}]");
            $nodeElement = $_[0];
            $node = $_[1];
            $this->resolveTransitionDestinations($this->getDirectChildrenByTagName($nodeElement, "transition"), $node);
            $this->log->debug("\tEND END END Working on uTD [{$_[1]->getName()}]");
        }
        $this->log->debug("<resolveAllTransitionDestinations()");
    }

    /**
     * @param array $transitions of \DOMNodes
     * @param Node $node
     */
    public function resolveTransitionDestinations($transitions = array(), Node $node) {
        foreach ( $transitions as $transitionElement ) {
            $this->resolveTransitionDestination($transitionElement, $node);
        }
    }

    public function resolveTransitionDestination(\DOMElement $transitionElement, Node $node) {
        /**
         * @var Transition
         */
        $transition = new Transition();
        $transition->setProcessDefinition($this->processDefinition);
        $transition->setName($transitionElement->getAttribute("name"));
        $transition->setDescription($this->getDescription($transitionElement));
        
	    // read transition condition
	    $condition = $transitionElement->getAttribute("condition");
	    if ($condition == "") {
	      $conditionElement = $transitionElement->getElementsByTagName("condition")[0];
	      if (!is_null($conditionElement)) {
	        $condition = $conditionElement->textContent();
	      }
	    }
	    $transition->setCondition($condition);
	        
        $node->addLeavingTransition($transition);
        
        // set destinationNode of the transition
        $toName = $transitionElement->getAttribute("to");
        if ( $toName == "" ) {
        	$this->log->debug("node '{$node->getFullyQualifiedName()} has a transition without a 'to'-attribute to specify its destinationNode");
            $this->addWarning("node '{$node->getFullyQualifiedName()} has a transition without a 'to'-attribute to specify its destinationNode");
        } else {
            $this->log->debug("Searching for transition destination [$toName] for transition [{$transition->getName()}] from node [{$node->getName()}]");
            $to = $node->getParent()->findNode($toName);
            if ( $to == null ) {
                $this->log->debug("\ttransition to [{$toName}] on node [{$node->getFullyQualifiedName()}] cannot be resolved");
                $this->addWarning("transition to [{$toName}] on node [{$node->getFullyQualifiedName()}] cannot be resolved");
            } else {
                $this->log->debug("\taddArriingTransition TO [{$to->getName()}]");
                $to->addArrivingTransition($transition);
            }
        }
        
        // read the actions
        $this->readActions($transitionElement, $transition, Event::EVENTTYPE_TRANSITION);
        
        $this->readExceptionHandlers($transitionElement, $transition);
    }

    public function addUnresolvedActionReference(\DOMElement $actionElement, Action $action) {
        $this->unresolvedActionReferences[] = ["element" => $actionElement, "action" => $action];
    }

    public function resolveActionReferences() {
        foreach ( $this->unresolvedActionReferences as $_ ) {
            $actionElement = $_["element"];
            $action = $_["action"];
            
            $referencedActionName = $actionElement->getAttribute("ref-name");
            $referencedAction = $this->processDefinition->getAction($referencedActionName);
            
            if ( is_null($referencedAction) ) {
                $this->addWarning("couldn't resolve action reference in [{$actionElement->getNodePath()}]");
                return;
            }
            $action->setReferencedAction($referencedAction);
        }
    }
    
    // verify swimlane assignments in second pass ///////////////////////////////
    public function verifySwimlaneAssignments() {
    	$taskMgmtDefinition = $this->processDefinition->getTaskMgmtDefinition();
    	$swimlanes = array();
    	
    	if (!is_null($taskMgmtDefinition) && (sizeof($taskMgmtDefinition->getSwimlanes())>0)) {
    		$swimlanes = $taskMgmtDefinition->getSwimlanes();
    		$startTask = $taskMgmtDefinition->getStartTask();
    		$startTaskSwimlane = (!is_null($startTask)) ? $startTask->getSwimlane() : null;

    		foreach ($swimlanes as $swimlane) {
    			if (is_null($swimlane->getAssignmentDelegation()) && $swimlane != $startTaskSwimlane) {
    				$this->addWarning("swimlane '" + $swimlane->getName() + "' does not have an assignment");
    			}
    		}
    	}
    }

    protected function parseProcessDefinitionAttributes(\DOMElement $root) {
        $this->processDefinition->setName($this->getAttributeValueOrEmptyString($root, "name"));
		$this->initialNodeName = $this->getAttributeValueOrEmptyString($root, "initial");
    }

    protected function getAttributeValueOrEmptyString(\DOMElement $elem, $attribute) {
        $value = $elem->getAttribute($attribute);
        return (is_null($value) ? "" : $value);
    }

    public function addError($description, $exception = null) {
        $this->log->error("invalid process xml: ", $description);
        $this->problemsErr[] = "Invalid process xml: $description";
    }

    public function addWarning($description) {
        $this->log->warn("process xml warning: " . $description);
        $this->problemsWarn[] = $description;
    }

    /**
     * @return ProcessDefinition
     */
    public function getProcessDefinition() {
        return $this->processDefinition;
    }
    
    /**
     * Stock DOMElement::getElementsByTagName goes deep. This just cares about direct children 
     * 
     * @param \DOMElemenet $elem
     * @param array<\DOMElemenet> $tagName
     */
    public function getDirectChildrenByTagName(\DOMElement $elem, $tagName) {
        $okChildren = [];
        foreach ($elem->childNodes as $possibleGoodElement) {
            if ($possibleGoodElement->nodeName !== $tagName) { continue; }
            $okChildren[] = $possibleGoodElement;
        }
        return $okChildren;
    }
}

function HandleXmlError($errno, $errstr, $errfile, $errline) {
    if ( $errno == E_WARNING && (substr_count($errstr, "DOMDocument::loadXML()") > 0) ) {
        throw new \DOMException($errstr);
    } else
        return false;
}

/**
 * @param string $strXml
 * @return \DOMDocument
 */
function XmlLoader($strXml) {
    set_error_handler('\com\coherentnetworksolutions\pbpm\jpdl\xml\HandleXmlError');
    $dom = new \DOMDocument();
    $dom->loadXml($strXml);
    restore_error_handler();
    return $dom;
}
