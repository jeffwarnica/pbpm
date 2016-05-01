<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\jpdl\xml\Parsable;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use com\coherentnetworksolutions\pbpm\instantiation\Delegation;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;

/** @Entity **/
class Action implements ActionHandler, Parsable {

    static $actionTypes =   [
        "action"       => "com\coherentnetworksolutions\pbpm\graph\def\Action",
        "create-timer" => "com\coherentnetworksolutions\pbpm\scheduler\def\CreateTimerAction",
        "cancel-timer" => "com\coherentnetworksolutions\pbpm\scheduler\def\CancelTimerAction",
//         "script"       => "org.jbpm.graph.action.Script",
        ];
    
    /** 
     * @Column(type="integer")
     * @Id 
     * @GeneratedValue(strategy="AUTO") 
     * @var int 
     * */
    public  $id;
    
    /**
     * @Column(type="string",nullable=true)
     * @var string
     */
    protected $name = null;
    
    /**
     * @Column(type="boolean")
     * @var boolean
     */
    protected $isPropagationAllowed = true;
    
    /**
     * @Column(type="boolean")
     * @var boolean
     */
    protected $isAsync = false;
    
    /**
     * @ManyToOne(targetEntity="Action")
     * @var Action
     */
    protected $referencedAction = null;
    
    /**
     * @OneToOne(targetEntity="com\coherentnetworksolutions\pbpm\instantiation\Delegation",cascade={"persist"})
     * @var Delegation
     */
    protected $actionDelegation  = null;

    /**
     * @Column(type="string", nullable=true)
     * @var string
     */
    protected $actionExpression = null;
    
    /**
     * @ManyToOne(targetEntity="Event", inversedBy="actions")
     * @var Event
     */
    protected $event = null;
    
    /**
     * @ManyToOne(targetEntity="processDefinition", inversedBy="actions")
     * @var ProcessDefinition
     */
    protected $processDefinition = null;

    /**
     * @var \Logger
     */
    protected $log;
    
    public function __construct(Delegation $actionDelegate = null) {
        $this->log = \Logger::getLogger(__CLASS__);
        $this->log->debug("__construct!!!");
        if ($actionDelegate instanceof Delegation) {
            $this->actionDelegation = $actionDelegate;
        }
    }

    public function __toString() {
        if ($this->name!=null) {
            $toString = "action[". $this->name . "]";
        } else if ( ($this->actionDelegation!=null) ) {
            $toString = get_class($this->actionDelegation);
        } else if ($this->actionExpression!=null) {
            $toString = $this->actionExpression;
        } else {
            $className = get_class($this);
            if ($this->name!=null) {
                $toString = $className . "(" . $this->name . ")";
            } else {
                $toString = $className . "("  . spl_object_hash($this) . ")";
            }
        }
        return $toString;
    }

    public function read(\DOMElement $actionElement, JpdlXmlReader $jpdlReader) {
        $this->log->debug("read() fr line no:" . $actionElement->getLineNo());
        $expression = $actionElement->getAttribute("expression");
        if (strlen($expression)>0) {
            $this->actionExpression = $expression;

        } else if ($actionElement->getAttribute("ref-name")!=="") {
            $jpdlReader->addUnresolvedActionReference($actionElement, $this);
        } else if ($actionElement->getAttribute("class")!=="") {
            $this->actionDelegation = new Delegation();
            $this->actionDelegation->read($actionElement, $jpdlReader);

        } else {
            $jpdlReader->addWarning("action does not have class nor ref-name attribute " .
                             $actionElement->ownerDocument->saveXML($actionElement));
        }

        $acceptPropagatedEvents = strtolower($actionElement->getAttribute("accept-propagated-events"));
        if (in_array($acceptPropagatedEvents, ["false", "no", "off"])) {
            $this->isPropagationAllowed = false;
        }

        $asyncText = strtolower($actionElement->getAttribute("async"));
        if ("true" === $asyncText) {
            $this->isAsync = true;
        }
    }

    /**
     * @see \com\coherentnetworksolutions\pbpm\jpdl\xml\Parsable::write()
     */
    public function write(\DOMElement $actionElement) {
        $this->log->debug("Action::write()");
        if (!is_null($this->actionDelegation)) {
            $this->log->debug(" \--To actionDelegation");
            $this->actionDelegation->write($actionElement);
        }
    }

    public function execute(ExecutionContext $executionContext)  {
    	$this->log->debug("EXEC exec()");
        if (!is_null($this->referencedAction)) {
        	$this->log->debug("\tTo execute() referencedAction");
            $this->referencedAction->execute($executionContext);
        } else if (!is_null($this->actionExpression)) {
            JbpmExpressionEvaluator::evaluate($this->actionExpression, $executionContext);

        } else if (!is_null($this->actionDelegation)) {
        	$this->log->debug("\tTo actionDelegation->getInstance()");
            /**
             * @var ActionHandler 
             */
            $actionHandler = $this->actionDelegation->getInstance();
            $clazz = get_class($actionHandler);
            $this->log->debug("\tto execute() actionHandler (class is: [{$clazz}])");
            $actionHandler->execute($executionContext);
            $this->log->debug("\t <<- and back");
            
        }
    }

    public function setName($name) {
        // if the process definition is already set
        if (!is_null($this->processDefinition)) {
            // update the process definition action map
            
            $actionMap = $this->processDefinition->getActions();
            // the != string comparison is to avoid null pointer checks.  it is no problem if the body is executed a few times too much :-)
            if ( ($this->name != $name)
                            && ($actionMap!=null) ) {
                                $actionMap->detach($this->name);
                                $actionMap->attach($name, $this);
                            }
        }

        // then update the name
        $this->name = $name;
    }

    // getters and setters //////////////////////////////////////////////////////

    /**
     * @return boolean
     */
    public function acceptsPropagatedEvents() {
        return $this->isPropagationAllowed;
    }

    /**
     * @return boolean
     */
    public function isPropagationAllowed() {
        return $this->isPropagationAllowed;
    }
    public function setPropagationAllowed($isPropagationAllowed) {
        $this->isPropagationAllowed = $isPropagationAllowed;
    }

    public function getId() {
        return $this->id;
    }
    public function getName() {
        return $this->name;
    }
    public function getEvent() {
        return $this->event;
    }
    /**
     * @return ProcessDefinition
     */
    public function getProcessDefinition() {
        return $this->processDefinition;
    }
    
    public function setProcessDefinition(ProcessDefinition $processDefinition) {
        $this->log->debug("setProcessDefinition() called");
        $this->processDefinition = $processDefinition;
    }
    
    /**
     * @return Delegation
     */
    public function getActionDelegation() {
        return $this->actionDelegation;
    }
    public function setActionDelegation(Delegation $instantiatableDelegate) {
        $this->actionDelegation = $instantiatableDelegate;
    }
    
    public function getReferencedAction() {
        return $this->referencedAction;
    }
    public function setReferencedAction(Action $referencedAction) {
        $this->referencedAction = $referencedAction;
    }
    public function isAsync() {
        return $this->isAsync;
    }
    public function getActionExpression() {
        return $this->actionExpression;
    }
    public function setActionExpression($actionExpression) {
        $this->actionExpression = $actionExpression;
    }
    public function setEvent(Event $event) {
        $this->log->debug("setEvent({$event->getEventType()})");
        $this->event = $event;
    }
    public function setAsync($isAsync) {
        $this->isAsync = $isAsync;
    }
    
    /**
     * Given an action type, returns a fully qualified class name
     * @param string $name
     * @return multitype:string |NULL Class name
     */
    public static function getActionType($name) {
        if (self::hasActionType($name)) {
            return self::$actionTypes[$name];
        } else {
            return null;
        }
    }
    
    public static function hasActionType($name) {
        return array_key_exists($name, self::$actionTypes);
    }
    
    /**
     * @param string $name of class
     * @return multitype:|NULL name of action type
     */
    public static function getActionName($name) {
        if (self::hasActionName($name)) {
            return array_flip(self::$actionTypes)[$name];
        } else {
            return null;
        }
    }
    
    public static function hasActionName($name) {
        return array_key_exists($name, array_flip(self::$actionTypes));
    }
}
