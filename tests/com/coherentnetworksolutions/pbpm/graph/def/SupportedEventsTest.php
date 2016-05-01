<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\graph\node\StartState;
use com\coherentnetworksolutions\pbpm\graph\node\State;
use com\coherentnetworksolutions\pbpm\graph\node\TaskNode;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Task;
use com\coherentnetworksolutions\pbpm\graph\node\EndState;
/**
 * All decendents of {@link org.jbpm.graph.def.GraphElement} have an concrete
 * implementation of the method getSupportedEventTypes() which returns a String
 * array of event names that are accepted by this graph element.
 *
 * This test case has two purposes: 1) insuring that the graph elements return
 * their expected list 2) document which graph elements support which events
 * through logging
 */
class SupportedEventsTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * @test
	 */
	public function testNodeEvents() {
		$this->assertSupportedEvents(new Node(), [ 
				"node-enter",
				"node-leave",
				"before-signal",
				"after-signal" 
		]);
	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testDecisionEvents() {
// 		$this->assertSupportedEvents(new Decision(), [ 
// 				"node-enter",
// 				"node-leave",
// 				"before-signal",
// 				"after-signal" 
// 		]);
// 	}
	
	/**
	 * @test
	 */
	public function testEndStateEvents() {
		$this->assertSupportedEvents(new EndState(), [ 
				"node-enter" 
		]);
	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testForkEvents() {
// 		$this->assertSupportedEvents(new Fork(), [ 
// 				"node-enter",
// 				"node-leave",
// 				"before-signal",
// 				"after-signal" 
// 		]);
// 	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testInterleaveEndEvents() {
// 		$this->assertSupportedEvents(new InterleaveEnd(), [ 
// 				"node-enter",
// 				"node-leave",
// 				"before-signal",
// 				"after-signal" 
// 		]);
// 	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testInterleaveStartEvents() {
// 		$this->assertSupportedEvents(new InterleaveStart(), [ 
// 				"node-enter",
// 				"node-leave",
// 				"before-signal",
// 				"after-signal" 
// 		]);
// 	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testJoinEvents() {
// 		$this->assertSupportedEvents(new Join(), [ 
// 				"node-enter",
// 				"node-leave",
// 				"before-signal",
// 				"after-signal" 
// 		]);
// 	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testMergeEvents() {
// 		$this->assertSupportedEvents(new Merge(), [ 
// 				"node-enter",
// 				"node-leave",
// 				"before-signal",
// 				"after-signal" 
// 		]);
// 	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testMilestoneNodeEvents() {
// 		$this->assertSupportedEvents(new MilestoneNode(), [ 
// 				"node-enter",
// 				"node-leave",
// 				"before-signal",
// 				"after-signal" 
// 		]);
// 	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testProcessStateEvents() {
// 		$this->assertSupportedEvents(new ProcessState(), [ 
// 				"node-leave",
// 				"node-enter",
// 				"after-signal",
// 				"before-signal",
// 				"subprocess-created",
// 				"subprocess-end" 
// 		]);
// 	}
	
	/**
	 * @test
	 */
	public function testStartStateEvents() {
		$this->assertSupportedEvents(new StartState(), [ 
				"node-leave",
				"after-signal" 
		]);
	}
	
	/**
	 * @test
	 */
	public function testStateEvents() {
		$this->assertSupportedEvents(new State(), [ 
				"node-enter",
				"node-leave",
				"before-signal",
				"after-signal" 
		]);
	}
	
	// 	/**
	// 	 * @test
	// 	 */
	// 	public function testSuperStateEvents() {
	// 		$this->assertSupportedEvents(new SuperState(), [ 
	// 				"transition",
	// 				"before-signal",
	// 				"after-signal",
	// 				"node-enter",
	// 				"node-leave",
	// 				"superstate-enter",
	// 				"superstate-leave",
	// 				"subprocess-created",
	// 				"subprocess-end",
	// 				"task-create",
	// 				"task-assign",
	// 				"task-start",
	// 				"task-end",
	// 				"timer" 
	// 		]);
	// 	}
	
	/**
	 * @test
	 */
	public function testTaskNodeEvents() {
		$this->assertSupportedEvents(new TaskNode(), [ 
				"node-enter",
				"node-leave",
				"before-signal",
				"after-signal" 
		]);
	}
	
	/**
	 * @test
	 */
	public function testTaskEvents() {
		$this->assertSupportedEvents(new Task(), [ 
				"task-create",
				"task-assign",
				"task-start",
				"task-end" 
		]);
	}
	
	/**
	 * @test
	 */
	public function testProcessDefinitionEvents() {
		$this->assertSupportedEvents(new ProcessDefinition(), [ 
				"transition",
				"before-signal",
				"after-signal",
				"process-start",
				"process-end",
				"node-enter",
				"node-leave",
				"superstate-enter",
				"superstate-leave",
				"subprocess-created",
				"subprocess-end",
				"task-create",
				"task-assign",
				"task-start",
				"task-end",
				"timer" 
		]);
	}
	
	/**
	 * @test
	 */
	public function testTransitionEvents() {
		$this->assertSupportedEvents(new Transition(), [ 
				"transition" 
		]);
	}
	private function assertSupportedEvents(GraphElement $graphElement, $expectedEventTypes = array()) {
		$supportedEventTypes = $graphElement->getSupportedEventTypes();
		
		$this->assertEquals(sort($expectedEventTypes), sort($supportedEventTypes), "Expected and supported arrays do not match.");
	}
	
	// private HashSet getHashSet(String[] strings) {
	// HashSet set = new HashSet();
	// for (int i = 0; i < strings.length; i++) {
	// set.add(strings[i]);
	// }
	// return set;
	// }
}
