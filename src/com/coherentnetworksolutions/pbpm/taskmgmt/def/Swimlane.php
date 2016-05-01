<?php
namespace com\coherentnetworksolutions\pbpm\taskmgmt\def;

use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\instantiation\Delegation;
/**
 * defines a task and how the actor must be calculated at runtime.
 * @entity
 */
class Swimlane {
	
    /**
     * @Id 
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     * @var int
     * */
    public $id;
    
    /**
     * @Column(type="string")
     * @var string
     **/
	protected $name;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 **/
	protected $actorIdExpression;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 **/
	protected $pooledActorsExpression;
	
	/**
	 * @ManyToOne(targetEntity="com\coherentnetworksolutions\pbpm\instantiation\Delegation",cascade={"persist"})
	 * //, inversedBy="exceptionHandlers"
	 * @var Delegation
	 */
	protected $assignmentDelegation;
	
	/**
	 * @ManyToOne(targetEntity="com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskMgmtDefinition",cascade={"persist"})
	 * //, inversedBy="exceptionHandlers"
	 * @var TaskMgmtDefinition 
	 */
	protected $taskMgmtDefinition;
	
	/**
	 * @OneToMany(targetEntity="Task",cascade={"persist"},mappedBy="graphElement")
	 * @var ArrayCollection
	 */
	protected $tasks;
	
	public function __construct($name) {
		$this->tasks = new ArrayCollection();
		$this->name = $name;
	}
	
	/**
	 * sets the taskMgmtDefinition unidirectionally. use
	 * TaskMgmtDefinition.addSwimlane to create a bidirectional relation.
	 */
	public function setTaskMgmtDefinition(TaskMgmtDefinition $taskMgmtDefinition) {
		$this->taskMgmtDefinition = $taskMgmtDefinition;
	}
	
	// tasks ////////////////////////////////////////////////////////////////////
	
	public function addTask(Task $task) {
		$this->tasks->add($task);
		$task->setSwimlane($this);
	}
	
	public function getTasks() {
		return $this->tasks;
	}
	
	// equals ///////////////////////////////////////////////////////////////////
	
	public function __equals($o) {
		if ($this == $o) {return true;}
		if (!($o instanceof Swimlane)) return false;
	
		if ($this->id != 0 && $this->id == $o->getId()) return true;
	
		return ($this->name === $o->getName()) && $this->taskMgmtDefinition->__equals($o->getTaskMgmtDefinition());
	}
	
	
	public function setActorIdExpression($actorIdExpression) {
		$this->actorIdExpression = $actorIdExpression;
		// combination of actorIdExpression and pooledActorsExpression is allowed
		// this.pooledActorsExpression = null;
		$this->assignmentDelegation = null;
	}
	
	public function setPooledActorsExpression($pooledActorsExpression) {
		// combination of actorIdExpression and pooledActorsExpression is allowed
		// this.actorIdExpression = null;
		$this->pooledActorsExpression = $pooledActorsExpression;
		$this->assignmentDelegation = null;
	}
		
	public function setAssignmentDelegation(Delegation $assignmentDelegation) {
		// assignment expressions and assignmentDelegation are mutually exclusive
		$this->actorIdExpression = null;
		$this->pooledActorsExpression = null;
		$this->assignmentDelegation = $assignmentDelegation;
	}
	
	// getters and setters //////////////////////////////////////////////////////
	
	public function getTaskMgmtDefinition() {
		return $this->taskMgmtDefinition;
	}
	
	public function getActorIdExpression() {
		return $this->actorIdExpression;
	}
	
	public function getPooledActorsExpression() {
		return $this->pooledActorsExpression;
	}
	
	/**
	 * @return \com\coherentnetworksolutions\pbpm\instantiation\Delegation
	 */
	public function getAssignmentDelegation() {
		return $this->assignmentDelegation;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getId() {
		return $this->id;
	}	
	
}