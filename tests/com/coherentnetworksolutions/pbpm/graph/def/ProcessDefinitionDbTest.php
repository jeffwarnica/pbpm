<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;
use com\coherentnetworksolutions\pbpm\graph\node\StartState;
use com\coherentnetworksolutions\pbpm\context\def\ContextDefinition;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskMgmtDefinition;

class ProcessDefinitionDbTest extends AbstractDbTestCase {
	
	/**
	 * @test
	 */
	public function testProcessDefinitionVersion() {
		$processDefinition = new ProcessDefinition("name");
		$processDefinition->setVersion(3);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition);
		$this->assertEquals(3, $processDefinition->getVersion());
	}
	
	/**
	 * @test
	 */
	public function testProcessDefinitionIsTerminationImplicit() {
		$processDefinition = new ProcessDefinition("name");
		$processDefinition->setTerminationImplicit(false);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition);
		$this->assertFalse($processDefinition->isTerminationImplicit());
	}
	
	/**
	 * @test
	 */
	public function testProcessDefinitionStartState() {
		$processDefinition = new ProcessDefinition();
		$processDefinition->setStartState(new StartState());
		
		$processDefinition = $this->saveAndReload($processDefinition);
		// the start state of a process definition is mapped as a node.
		// therefor the hibernate proxy will be a node
		$startState = $processDefinition->getStartState();
		$this->assertTrue($startState instanceof Node, "startState should be a sub class or sub interface of Node.");
		// reloading gives a better typed proxy
		// $this->assertTrue(
		// StartState.class.isAssignableFrom(session.load(StartState.class, new Long(
		// startState.getId())).getClass()));
	}
	
	/**
	 * @test
	 */
	public function testProcessDefinitionNodes() {
		$processDefinition = new ProcessDefinition();
		$processDefinition->setStartState(new StartState("s"));
		$processDefinition->addNode(new Node("a"));
		$processDefinition->addNode(new Node("b"));
		$processDefinition->addNode(new Node("c"));
		$processDefinition->addNode(new Node("d"));
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertEquals("s", $processDefinition->getStartState()->getName());
		$this->assertEquals("a", $processDefinition->getNodes()[0]->getName());
		$this->assertEquals("b", $processDefinition->getNodes()[1]->getName());
		$this->assertEquals("c", $processDefinition->getNodes()[2]->getName());
		$this->assertEquals("d", $processDefinition->getNodes()[3]->getName());
	}
	
	/**
	 * @test
	 */
	public function testActions() {
		$processDefinition = new ProcessDefinition();
		$action = new Action();
		$action->setName("a");
		$processDefinition->addAction($action);
		$action = new Action();
		$action->setName("b");
		$processDefinition->addAction($action);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertEquals(2, sizeof($processDefinition->getActions()));
		$this->assertNotNull($processDefinition->getActions()->get("a"));
		$this->assertNotNull($processDefinition->getActions()->get("b"));
		$this->assertTrue($processDefinition->getAction("a") instanceof Action);
		$this->assertTrue($processDefinition->getAction("b") instanceof Action);
	}
	
	/**
	 * @test
	 */
	public function testEvents() {
		$processDefinition = new ProcessDefinition();
		$processDefinition->addEvent(new Event("node-enter"));
		$processDefinition->addEvent(new Event("node-leave"));
		$processDefinition->addEvent(new Event("transition"));
		$processDefinition->addEvent(new Event("process-start"));
		$processDefinition->addEvent(new Event("process-end"));
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition->getEvent("node-enter"));
		$this->assertNotNull($processDefinition->getEvent("node-leave"));
		$this->assertNotNull($processDefinition->getEvent("transition"));
		$this->assertNotNull($processDefinition->getEvent("process-start"));
		$this->assertNotNull($processDefinition->getEvent("process-end"));
	}
	
	/**
	 * @test
	 */
	public function testExceptionHandlers() {
		$processDefinition = new ProcessDefinition();
		$exceptionHandler = new ExceptionHandler();
		$exceptionHandler->setExceptionClassName("org.disaster.FirstException");
		$processDefinition->addExceptionHandler($exceptionHandler);
		$exceptionHandler = new ExceptionHandler();
		$exceptionHandler->setExceptionClassName("org.disaster.SecondException");
		$processDefinition->addExceptionHandler($exceptionHandler);
		$exceptionHandler = new ExceptionHandler();
		$exceptionHandler->setExceptionClassName("org.disaster.ThirdException");
		$processDefinition->addExceptionHandler($exceptionHandler);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertEquals("org.disaster.FirstException", $processDefinition->getExceptionHandlers()[0]->getExceptionClassName());
		$this->assertEquals("org.disaster.SecondException", $processDefinition->getExceptionHandlers()[1]->getExceptionClassName());
		$this->assertEquals("org.disaster.ThirdException", $processDefinition->getExceptionHandlers()[2]->getExceptionClassName());
	}
	
	/**
	 * @test
	 */
	public function testContextModuleDefinition() {
		$processDefinition = new ProcessDefinition();
		$processDefinition->addDefinition(new ContextDefinition());
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition->getContextDefinition());
		$this->assertTrue($processDefinition->getContextDefinition() instanceof ContextDefinition);
	}
	
	// /**
	// * @test
	// * @skip
	// * @todo FileDefinition
	// */
	// public function testFileDefinition() {
	// $processDefinition = new ProcessDefinition();
	// $processDefinition->addDefinition(new FileDefinition());
	
	// $processDefinition = $this->saveAndReload($processDefinition);
	// $this->assertNotNull($processDefinition->getFileDefinition());
	// $this->assertSame($processDefinition->getFileDefinition() instanceof FileDefinition);
	// }
	
	/**
	 * @test
	 */
	public function testTaskMgmtDefinition() {
		$processDefinition = new ProcessDefinition();
		$processDefinition->addDefinition(new TaskMgmtDefinition());
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition->getTaskMgmtDefinition());
		$this->assertTrue($processDefinition->getTaskMgmtDefinition() instanceof TaskMgmtDefinition);
	}
}
