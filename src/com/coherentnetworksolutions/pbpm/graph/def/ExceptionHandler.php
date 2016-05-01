<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;

/**
 * @Entity *
 */
class ExceptionHandler {
	
	/**
	 * @Column(type="integer")
	 * @Id
	 * @GeneratedValue(strategy="AUTO")
	 * 
	 * @var int
	 *
	 */
	public $id;
	
	/**
	 * @Column(type="string")
	 * 
	 * @var string
	 */
	protected $exceptionClassName = null;
	
	/**
	 * @ManyToOne(targetEntity="com\coherentnetworksolutions\pbpm\graph\def\GraphElement", inversedBy="exceptionHandlers")
	 * 
	 * @var ProcessDefinition
	 */
	protected $graphElement = null;
	
	/**
	 * @OneToMany(targetEntity="Action",mappedBy="exceptionHandler",cascade={"persist"})
	 * 
	 * @var ArrayCollection of Action
	 */
	protected $actions;
	
	/**
	 * @var \Logger $log
	 */
	protected $log;
	
	public function __construct() {
		$this->log = \Logger::getLogger(__CLASS__);
		$this->actions = new ArrayCollection();
		$this->graphElement = new ArrayCollection();
	}
	
	public function matches(\Exception $exception) {
		$this->log->debug("EXEC matches()");
		$matches = true;
		if ($this->exceptionClassName != "") {
			$clazz = $this->exceptionClassName;
			$exclass = get_class($exception);
			$this->log->debug("\t Is [$exclass] instance of [$clazz]??");
			if ($exclass instanceof $clazz) {
				$matches = false;
			}
		}
		return $matches;
	}
	public function handleException(ExecutionContext $executionContext) {
		if (sizeof($this->actions) > 0) {
			$iter = $this->actions->getIterator();
			while ( $iter->valid() ) {
				$action = $iter->current();
				$action->execute($executionContext);
				$iter->next();
			}
		}
	}
	
	// actions
	// ///////////////////////////////////////////////////////////////////////////
	public function getActions() {
		return $this->actions;
	}
	public function addAction(Action $action) {
		$this->actions->add($action);
	}
	public function removeAction(Action $action) {
		$this->actions->remove(action);
	}
	
	// public void reorderAction(int oldIndex, int newIndex) {
	// if (actions!=null) {
	// actions.add(newIndex, actions.remove(oldIndex));
	// }
	// }
	
	// getters and setters
	// ///////////////////////////////////////////////////////////////////////////
	public function getExceptionClassName() {
		return $this->exceptionClassName;
	}
	public function setExceptionClassName($exceptionClassName) {
		$this->exceptionClassName = $exceptionClassName;
	}
	public function getGraphElement() {
		return $this->graphElement;
	}
	public function clearGraphElement() {
		$this->graphElement = null;
	}
	public function setGraphElement(GraphElement $graphElement) {
		$this->graphElement = $graphElement;
	}
}