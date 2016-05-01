<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class NodeDbTest extends AbstractDbTestCase {
	
	/**
	 * @test
	 */
	public function testNodeName() {
		$node = new Node("n");
		
		$processDefinition = new ProcessDefinition();
		$processDefinition->addNode($node);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition);
		
		$this->assertEquals("n", $processDefinition->getNode("n")->getName());
	}
	/**
	 * @test
	 */
	public function testNodeProcessDefinition() {
		$node = new Node("n");
		
		$processDefinition = new ProcessDefinition();
		$processDefinition->addNode($node);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition);
		$this->assertSame($processDefinition, $processDefinition->getNode("n")->getProcessDefinition());
	}
	/**
	 * @test
	 */
	public function testNodeEvents() {
		$node = new Node("n");
		$node->addEvent(new Event("node-enter"));
		$node->addEvent(new Event("node-leave"));
		$node->addEvent(new Event("transition"));
		$node->addEvent(new Event("process-start"));
		$node->addEvent(new Event("process-end"));
		
		$processDefinition = new ProcessDefinition();
		$processDefinition->addNode($node);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$node = $processDefinition->getNode("n");
		$this->assertNotNull($node->getEvent("node-enter"));
		$this->assertNotNull($node->getEvent("node-leave"));
		$this->assertNotNull($node->getEvent("transition"));
		$this->assertNotNull($node->getEvent("process-start"));
		$this->assertNotNull($node->getEvent("process-end"));
	}
	
	/**
	 * @test
	 */
	public function testNodeExceptionHandlers() {
		$exceptionHandler1 = new ExceptionHandler();
		$exceptionHandler1->setExceptionClassName("org.disaster.FirstException");
		
		$exceptionHandler2 = new ExceptionHandler();
		$exceptionHandler2->setExceptionClassName("org.disaster.SecondException");
		
		$exceptionHandler3 = new ExceptionHandler();
		$exceptionHandler3->setExceptionClassName("org.disaster.ThirdException");
		
		$node = new Node("n");
		$node->addExceptionHandler($exceptionHandler1);
		$node->addExceptionHandler($exceptionHandler2);
		$node->addExceptionHandler($exceptionHandler3);
		
		$processDefinition = new ProcessDefinition();
		$processDefinition->addNode($node);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$node = $processDefinition->getNode("n");
		$exceptionHandlers = $node->getExceptionHandlers();
		
		$exceptionHandler1 = $exceptionHandlers[0];
		$this->assertEquals("org.disaster.FirstException", $exceptionHandler1->getExceptionClassName());
		
		$exceptionHandler2 = $exceptionHandlers[1];
		$this->assertEquals("org.disaster.SecondException", $exceptionHandler2->getExceptionClassName());
		
		$exceptionHandler3 = $exceptionHandlers[2];
		$this->assertEquals("org.disaster.ThirdException", $exceptionHandler3->getExceptionClassName());
	}
	/**
	 * @test
	 */
	public function testNodeLeavingTransitions() {
		$a = new Node("a");
		$b = new Node("b");
		
		$processDefinition = new ProcessDefinition();
		$processDefinition->addNode($a);
		$processDefinition->addNode($b);
		
		$t = new Transition("one");
		$a->addLeavingTransition($t);
		$b->addArrivingTransition($t);
		
		$t = new Transition("two");
		$a->addLeavingTransition($t);
		$b->addArrivingTransition($t);
		
		$t = new Transition("three");
		$a->addLeavingTransition($t);
		$b->addArrivingTransition($t);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$a = $processDefinition->getNode("a");
		$b = $processDefinition->getNode("b");
		
		$this->assertEquals("one", $a->getLeavingTransitionsList()[0]->getName());
		$this->assertEquals("two", $a->getLeavingTransitionsList()[1]->getName());
		$this->assertEquals("three", $a->getLeavingTransitionsList()[2]->getName());
		
		$this->assertSame($b, $a->getLeavingTransition("one")->getTo());
		$this->assertSame($b, $a->getLeavingTransition("two")->getTo());
		$this->assertSame($b, $a->getLeavingTransition("three")->getTo());
	}
	
	/**
	 * @test
	 */
	public function testNodeArrivingTransitions() {
		$a = new Node("a");
		$b = new Node("b");
		
		$processDefinition = new ProcessDefinition();
		$processDefinition->addNode($a);
		$processDefinition->addNode($b);
		
		$t = new Transition("one");
		$a->addLeavingTransition($t);
		$b->addArrivingTransition($t);
		
		$t = new Transition("two");
		$a->addLeavingTransition($t);
		$b->addArrivingTransition($t);
		
		$t = new Transition("three");
		$a->addLeavingTransition($t);
		$b->addArrivingTransition($t);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$a = $processDefinition->getNode("a");
		$b = $processDefinition->getNode("b");
		
		$arrivingTransitionIter = $b->getArrivingTransitions()->getIterator();
		while ( $arrivingTransitionIter->valid() ){
			$_ = $arrivingTransitionIter->current();
			$this->assertSame($b, $_->getTo());
			$arrivingTransitionIter->next();
		}
		
		$expectedTransitionNames = array_flip([ 
				"one",
				"two",
				"three" 
		]);
		
		$arrivingTransitionIter = $b->getArrivingTransitions()->getIterator();
		while ( $arrivingTransitionIter->valid() ){
			$_ = $arrivingTransitionIter->current();
			unset($expectedTransitionNames[$_->getName()]);
			$arrivingTransitionIter->next();
		}
		$this->assertEquals(0, sizeof($expectedTransitionNames));
	}
	
	/**
	 * @test
	 */
	public function testNodeAction() {
		$action = new Action();
		$action->setName("a");
		
		$node = new Node("n");
		$node->setAction($action);
		
		$processDefinition = new ProcessDefinition();
		$processDefinition->addNode($node);
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$this->assertNotNull($processDefinition->getNode("n")->getAction());
	}
	
	// @TODO: superstate
	
	// public function testNodeSuperState() {
	// $node = new Node("n");
	
	// SuperState superState = new SuperState("s");
	// superState->addNode(node);
	
	// $processDefinition = new ProcessDefinition();
	// processDefinition->addNode(superState);
	
	// processDefinition = saveAndReload(processDefinition);
	// superState = (SuperState) processDefinition->getNode("s");
	// node = superState->getNode("n");
	// $this->assertNotNull(node);
	// $this->assertNotNull(superState);
	// $this->assertSame(node, superState->getNode("n"));
	// }
}
