<?php
namespace com\coherentnetworksolutions\pbpm\taskmgmt\def;
use com\coherentnetworksolutions\pbpm\module\def\ModuleDefinition;
use Doctrine\Common\Collections\ArrayCollection;

/** @Entity **/
class TaskMgmtDefinition extends ModuleDefinition {

    /**
     * @OneToMany(targetEntity="Swimlane",cascade={"persist"},mappedBy="swimlane")
     * @var ArrayCollection
     */
    protected $swimlanes;

    /**
     * @OneToMany(targetEntity="Task",cascade={"persist"},mappedBy="taskMgmtDefinition")
     * @var ArrayCollection
     */
    protected $tasks;

    /**
     * @OneToOne(targetEntity="Task",cascade={"persist"})
     * @var StartTask
     */
    protected $startTask;
    
    /**
     * @var \Logger
     */
    protected $log;
    
    
    // constructors /////////////////////////////////////////////////////////////
    public function __construct() {
    	$this->log = \Logger::getLogger(__CLASS__);
        $this->swimlanes = new ArrayCollection();
        $this->tasks = new ArrayCollection();
    }

    /**
     * @return ModuleInstance
     * @todo this
     */
    public function createInstance() {
//         return new TaskMgmtInstance($this);
    }
    
    // swimlanes ////////////////////////////////////////////////////////////////
    public function addSwimlane(Swimlane $swimlane) {
        $this->swimlanes->set($swimlane->getName(), $swimlane);
        $swimlane->setTaskMgmtDefinition($this);
        $this->log->debug("Just added a swimlane {$swimlane->getName()}");
    }

    /**
     * @return ArrayCollection
     */
    public function getSwimlanes() {
        return $this->swimlanes;
    }

    /**
     * @param string $swimlaneName
     * @return Swimlane
     */
    public function getSwimlane($swimlaneName) {
        $swimlane =  $this->swimlanes->get($swimlaneName);
        return $swimlane;
    }
    
    // tasks ////////////////////////////////////////////////////////////////////
    public function addTask(Task $task) {
        $this->tasks->set($task->getName(), $task);
        $task->setTaskMgmtDefinition($this);
    }

    public function getTasks() {
        return $this->tasks;
    }

    public function getTask($taskName) {
        return $this->tasks->get($taskName);
    }
    
    // start task ///////////////////////////////////////////////////////////////
    /**
     * @return \com\coherentnetworksolutions\pbpm\taskmgmt\def\StartTask
     */
    public function getStartTask() {
        return $this->startTask;
    }

    public function setStartTask(Task $startTask) {
        $this->startTask = $startTask;
    }
}
