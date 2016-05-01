<?php
namespace com\coherentnetworksolutions\pbpm\taskmgmt\def;

use com\coherentnetworksolutions\pbpm\graph\def\GraphElement;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\node\TaskNode;
use com\coherentnetworksolutions\pbpm\instantiation\Delegation;

/**
 * defines a task and how the actor must be calculated at runtime.
 * @entity
 */
class Task extends GraphElement {
    
    public static $PRIORITY_HIGHEST = 1;
    public static $PRIORITY_HIGH = 2;
    public static $PRIORITY_NORMAL = 3;
    public static $PRIORITY_LOW = 4;
    public static $PRIORITY_LOWEST = 5;

    public static function parsePriority($priorityText) {
        $priorityText = strtolower($priorityText);
        
        if ("highest" === $priorityText) { return self::PRIORITY_HIGHEST; }
        else if ("high" === $priorityText) { return self::PRIORITY_HIGH; }
        else if ("normal" === $priorityText) { return self::PRIORITY_NORMAL;}
        else if ("low" === $priorityText) { return self::PRIORITY_LOW;}
        else if ("lowest" === $priorityText) { return self::PRIORITY_LOWEST;}
        if (is_int($priorityText) && ($priorityText >= 1 && $priorityText <= 5 )) {
            return $priorityText;
        } else {
            throw new \Exception("priority '{$priorityText}' could not be parsed as a priority");
        }
    }

    /**
     * @Column(type="string", nullable=true)
     * @var string
     */
    protected $description = null;
    
    /**  @Column(type="boolean") **/
    protected $isBlocking = false;
    /**  @Column(type="boolean") **/
    protected $isSignalling = true;
//     /**  @Column(type="datetime") **/
    protected $dueDate = null;
    /**  @Column(type="integer") **/
    protected $priority = 3; //self::PRIORITY_NORMAL;
    
    /**
     * @OneToOne(targetEntity="com\coherentnetworksolutions\pbpm\graph\node\TaskNode",cascade={"persist"})
     * @var TaskNode
     */
    protected $taskNode = null;
    /**
     * @OneToOne(targetEntity="com\coherentnetworksolutions\pbpm\graph\node\StartState",cascade={"persist"})
     * @var StartState
     */
    protected $startState = null;
    
    /**
     * @ManyToOne(targetEntity="TaskMgmtDefinition",cascade={"persist"})
     * @var TaskMgmtDefinition
     */
    protected $taskMgmtDefinition = null;
    
    /**
     * @ManyToOne(targetEntity="Swimlane",cascade={"persist"})
     * @var Swimlane $swimlane
     */
    protected $swimlane = null;
    /**  @Column(type="string") **/
    protected $actorIdExpression = null;
    /**  @Column(type="string") **/
    protected $pooledActorsExpression = null;
    
//     protected Delegation assignmentDelegation = null;

    public static $supportedEventTypes = [
        Event::EVENTTYPE_TASK_CREATE,
        Event::EVENTTYPE_TASK_ASSIGN,
        Event::EVENTTYPE_TASK_START,
        Event::EVENTTYPE_TASK_END,
    ];

    /**
     * @var TaskController
     */
    protected $taskController = null;

    public function __construct($name = null) {
//         $this->name = $name;
        parent::__construct($name);
    }

    public function getSupportedEventTypes() {
        return self::$supportedEventTypes;
    }
    

//     // task instance factory methods ////////////////////////////////////////////

    /**
     * sets the taskNode unidirectionally.  use {@link TaskNode#addTask(Task)} to create
     * a bidirectional relation.
     */
    public function setTaskNode(TaskNode $taskNode) {
        $this->taskNode = $taskNode;
    }

    /**
     * sets the taskMgmtDefinition unidirectionally.  use TaskMgmtDefinition.addTask to create
     * a bidirectional relation.
     */
    public function setTaskMgmtDefinition(TaskMgmtDefinition $taskMgmtDefinition) {
        $this->taskMgmtDefinition = $taskMgmtDefinition;
    }

    /**
     * sets the swimlane.  Since a task can have max one of swimlane or assignmentHandler,
     * this method removes the swimlane if it is set.
     */
    public function setAssignmentDelegation(Delegation $assignmentDelegation) {
        $this->actorIdExpression = null;
        $this->pooledActorsExpression = null;
        $this->assignmentDelegation = $assignmentDelegation;
        $this->swimlane = null;
    }
    /**
     * sets the actorId expression.  The assignmentExpression is a JSF-like
     * expression to perform assignment.  Since a task can have max one of swimlane or
     * assignmentHandler, this method removes the swimlane and assignmentDelegation if
     * it is set.
     */
    public function setActorIdExpression($actorIdExpression) {
        $this->actorIdExpression = $actorIdExpression;
        // Note: combination of actorIdExpression and pooledActorsExpression is allowed
        // $this->pooledActorsExpression = null;
        $this->assignmentDelegation = null;
        $this->swimlane = null;
    }
    /**
     * sets the actorId expression.  The assignmentExpression is a JSF-like
     * expression to perform assignment.  Since a task can have max one of swimlane or
     * assignmentHandler, this method removes the other forms of assignment.
     */
    public function setPooledActorsExpression($pooledActorsExpression) {
        // Note: combination of actorIdExpression and pooledActorsExpression is allowed
        // $this->actorIdExpression = null;
        $this->pooledActorsExpression = $pooledActorsExpression;
        $this->assignmentDelegation = null;
        $this->swimlane = null;
    }
    /**
     * sets the swimlane unidirectionally.  Since a task can have max one of swimlane or assignmentHandler,
     * this method removes the assignmentHandler and assignmentExpression if one of those isset.  To create
     * a bidirectional relation, use {@link Swimlane#addTask(Task)}.
     */
    public function setSwimlane(Swimlane $swimlane) {
        $this->actorIdExpression = null;
        $this->pooledActorsExpression = null;
        $this->assignmentDelegation = null;
        $this->log->debug("IM SETTING MY SWIMLANE TO {$swimlane->getName()}");
        $this->swimlane = $swimlane;
    }

//     // parent ///////////////////////////////////////////////////////////////////

//     public GraphElement getParent() {
//         if (taskNode!=null) {
//             return taskNode;
//         }
//         if (startState!=null) {
//             return startState;
//         }
//         return processDefinition;
//     }

//     // getters and setters //////////////////////////////////////////////////////

    public function getTaskMgmtDefinition() {
        return $this->taskMgmtDefinition;
    }
    public function getDescription() {
    	$this->log->debug("getDescription returning [{$this->description}]");
        return $this->description;
    }
    public function setDescription($description) {
        $this->description = $description;
    }
    public function getSwimlane() {
        return $this->swimlane;
    }
    public function isBlocking() {
        return $this->isBlocking;
    }
    public function setBlocking($isBlocking) {
        $this->isBlocking = $isBlocking;
    }
    public function getTaskNode() {
        return $this->taskNode;
    }
    public function getActorIdExpression() {
        return $this->actorIdExpression;
    }
    public function getPooledActorsExpression() {
        return $this->pooledActorsExpression;
    }
    public function getAssignmentDelegation() {
        return $this->assignmentDelegation;
    }
    public function getDueDate() {
        return $this->dueDate;
    }
    public function setDueDate($duedate) {
        $this->dueDate = $duedate;
    }
    /**
     * @return \com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskController
     */
    public function getTaskController() {
        return $this->taskController;
    }
    public function setTaskController(TaskController $taskController) {
        $this->taskController = $taskController;
    }
    public function getPriority() {
        return $this->priority;
    }
    public function setPriority($priority) {
        $this->priority = $priority;
    }
    public function getStartState() {
        return $this->startState;
    }
    public function setStartState($startState) {
        $this->startState = $startState;
    }
    public function isSignalling() {
        return $this->isSignalling;
    }
    public function setSignalling($isSignalling) {
        $this->isSignalling = $isSignalling;
    }
}
