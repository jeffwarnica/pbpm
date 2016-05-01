<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;
class ParentTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * @test
	 */
	public function testProcessDefinitionParent() {
		$pd = new ProcessDefinition();
		$this->assertNull($pd->getParent());
	}
	
	/**
	 * @test
	 */
	public function testNodeInProcessParents() {
		$processDefinition = ProcessDefinition::parseXmlString("
			<process-definition>
			 <start-state name='start'>
			 <transition to='state'/>
			 </start-state>
			 <state name='state'>
			 <transition to='end'/>
			 </state>
			 <end-state name='end'/>
			</process-definition>
		");
		
		$this->assertSame($processDefinition, $processDefinition->getStartState()->getParent());
		$this->assertSame($processDefinition, $processDefinition->getNode("state")->getParent());
		$this->assertSame($processDefinition, $processDefinition->getNode("end")->getParent());
	}
	
	/**
	 * @test
	 */
	public function testTransitionInProcessParents() {
		$processDefinition = ProcessDefinition::parseXmlString("
	<process-definition>
	 <start-state name='start'>
	 <transition to='state'/>
	 </start-state>
	 <state name='state'>
	 <transition to='end'/>
	 </state>
	 <end-state name='end'/>
	</process-definition>");
		
		$this->assertSame($processDefinition, $processDefinition->getStartState()->getDefaultLeavingTransition()->getParent());
		$this->assertSame($processDefinition, $processDefinition->getNode("state")->getDefaultLeavingTransition()->getParent());
	}
	
	/**
	 * @test
	 */
	public function testNodeInSuperProcessParent() {
		$processDefinition = ProcessDefinition::parseXmlString("
	<process-definition>
	 <start-state name='start'>
	 <transition to='superstate/state'/>
	 </start-state>
	 <super-state name='superstate'>
	 <state name='state'>
	 <transition to='../end'/>
	 </state>
	 </super-state>
	 <end-state name='end'/>
	</process-definition>");
		
		$superState = $processDefinition->getNode("superstate");
		
		$this->assertSame($processDefinition, $processDefinition->getStartState()->getParent());
		$this->assertSame($processDefinition, $superState->getParent());
		$this->assertSame($processDefinition, $processDefinition->getNode("end")->getParent());
		$this->assertSame($superState, $processDefinition->findNode("superstate/state")->getParent());
	}
	
	/**
	 * @test
	 */
	public function testTransitionInSuperProcessParent() {
		$processDefinition = ProcessDefinition::parseXmlString("
	<process-definition name='imaprocessdefinition'>
		<start-state name='start'>
			<transition name='frstart' to='superstate/state'/>
		</start-state>
		<super-state name='superstate'>
	 		<state name='state'>
	 			<transition name='toend' to='../end'/>
		 		<transition name='loop' to='state'/>
		 		<transition name='tostate2' to='state2'/>
		 	</state>
	 		<state name='state2' />
	 	</super-state>
	 	<end-state name='end'/>
	</process-definition>");
		
		$superState = $processDefinition->getNode("superstate");
		
		$this->assertSame($processDefinition, $processDefinition->getStartState()->getDefaultLeavingTransition()->getParent());
		$this->assertSame($processDefinition, $processDefinition->findNode("superstate/state")->getDefaultLeavingTransition()->getParent());
	
		$this->assertSame($superState, $processDefinition->findNode("superstate/state")->getLeavingTransition("loop")->getParent());
		$this->assertSame($superState, $processDefinition->findNode("superstate/state")->getLeavingTransition("tostate2")->getParent());
	}
}
