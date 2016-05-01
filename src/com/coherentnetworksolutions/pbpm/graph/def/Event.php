<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

use Doctrine\Common\Collections\ArrayCollection;

/** @Entity **/
class Event {

    const EVENTTYPE_TRANSITION = "transition";
    const EVENTTYPE_BEFORE_SIGNAL = "before-signal";
    const EVENTTYPE_AFTER_SIGNAL = "after-signal";
    const EVENTTYPE_PROCESS_START = "process-start";
    const EVENTTYPE_PROCESS_END = "process-end";
    const EVENTTYPE_NODE_ENTER = "node-enter";
    const EVENTTYPE_NODE_LEAVE = "node-leave";
    const EVENTTYPE_SUPERSTATE_ENTER = "superstate-enter";
    const EVENTTYPE_SUPERSTATE_LEAVE = "superstate-leave";
    const EVENTTYPE_SUBPROCESS_CREATED = "subprocess-created";
    const EVENTTYPE_SUBPROCESS_END = "subprocess-end";
    const EVENTTYPE_TASK_CREATE = "task-create";
    const EVENTTYPE_TASK_ASSIGN = "task-assign";
    const EVENTTYPE_TASK_START = "task-start";
    const EVENTTYPE_TASK_END = "task-end";
    const EVENTTYPE_TIMER = "timer";

    /** 
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @var int 
     * */
    public  $id;
    
    /**
     * @Column(type="string")
     * @var string
     */
    protected $eventType = null;
    
    /**
     * @ManyToOne(targetEntity="GraphElement")
     * @var GraphElement 
     */
    protected $graphElement = null;
    
    /**
     * @OneToMany(targetEntity="Action",mappedBy="event",cascade={"persist"})
     * @var ArrayCollection of Action
     */
    protected $actions;

    /**
     * @var \Logger
     */
    protected $log;
    // constructors /////////////////////////////////////////////////////////////

    public function __construct($eventTypeOrElement = null, $eventType = null) {
        $this->log = \Logger::getLogger(__CLASS__);
        $this->log->debug("__construct()");
        if (is_a($eventTypeOrElement, "Event")) {
            $this->graphElement = $eventTypeOrElement;
            $this->eventType = $eventType;
        } else {
            $this->eventType = $eventTypeOrElement;
        }
        $this->actions = new ArrayCollection();
    }


    // actions //////////////////////////////////////////////////////////////////

    /**
     * is the list of actions associated to this event.
     * @return ArrayCollection 
     */
    public function getActions() {
        return $this->actions;
    }

    public function hasActions() {
        return count($this->actions);
    }

    public function addAction(Action $action =  null) {
        if (is_null($action)) throw new \Exception("can't add a null action to an event");
        $this->log->debug("going to add action id:[{$action->getId()}]");
        $this->actions[$action->getId()] = $action;
        $this->log->debug("Sizeof actions is now: " . sizeof($this->actions));
        $action->setEvent($this);
        return $action;
    }

    public function removeAction(Action $action =  null) {
        if (is_null($action)) throw new \Exception("can't remove a null action from an event");
        unset($this->actions[$action->getId()]);
    }

    public function __toString() {
        return $this->eventType;
    }

    // equals ///////////////////////////////////////////////////////////////////
    // hack to support comparing hibernate proxies against the real objects
    // since this always falls back to ==, we don't need to overwrite the hashcode
//     public boolean equals(Object o) {
//         return EqualsUtil.equals(this, o);
//     }

    // getters and setters //////////////////////////////////////////////////////

    public function getEventType() {
        return $this->eventType;
    }
    /**
     * @return GraphElement
     */
    public function getGraphElement() {
        return $this->graphElement;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setGraphElement(GraphElement $ge) {
        $this->graphElement = $ge;
    }

    // private static final Log log = LogFactory.getLog(Event.class);
}