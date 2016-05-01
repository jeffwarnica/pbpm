<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\ActionHandler;

class EventPropagationTest extends \PHPUnit_Framework_TestCase {
	static $executedActions = [ ];
	// let's count the number of messages printed.
	// that way we have something to assert at the end of the test.
	static $nbrOfMessagesPrinted = 0;
	
	/**
	 * @before
	 * 
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		EventPropagationTest::$executedActions = [ ];
		EventPropagationTest::$nbrOfMessagesPrinted = 0;
	}
	public function testNodeToProcessEventPropagation() {
		$processDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <event type='node-enter'>
          <action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Recorder' />
        </event>
        <event type='node-leave'>
          <action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Recorder' />
        </event>
        <start-state name='start'>
          <transition to='state'/>
        </start-state>
        <state name='state'>
          <transition to='end'/>
        </state>
        <end-state name='end'/>
      </process-definition>
    ");
		// create the process instance
		$processInstance = new ProcessInstance($processDefinition);
		$this->assertEquals(0, sizeof(EventPropagationTest::$executedActions));
		
		$processInstance->signal();
		
		$this->assertEquals(2, sizeof(EventPropagationTest::$executedActions));
		
		$executedAction = EventPropagationTest::$executedActions[0];
		$this->assertEquals("node-leave", $executedAction->event->getEventType());
		$this->assertSame($processDefinition, $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->getStartState(), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertSame($processDefinition->getStartState(), $executedAction->node);
		
		$executedAction = EventPropagationTest::$executedActions[1];
		$this->assertEquals("node-enter", $executedAction->event->getEventType());
		$this->assertSame($processDefinition, $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->getNode("state"), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertSame($processDefinition->getNode("state"), $executedAction->node);
		
		$processInstance->signal();
		
		$this->assertEquals(4, sizeof(EventPropagationTest::$executedActions));
		
		$executedAction = EventPropagationTest::$executedActions[2];
		
		$this->assertSame($processDefinition, $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->getNode("state"), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertSame($processDefinition->getNode("state"), $executedAction->node);
		
		$executedAction = EventPropagationTest::$executedActions[3];
		$this->assertEquals("node-enter", $executedAction->event->getEventType());
		$this->assertSame($processDefinition, $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->getNode("end"), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertSame($processDefinition->getNode("end"), $executedAction->node);
	}
	public function testTransitionToProcessEventPropagation() {
		$processDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <event type='transition'>
    		<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Recorder' />
        </event>
        <start-state name='start'>
          <transition to='state'/>
        </start-state>
        <state name='state'>
          <transition to='end'/>
        </state>
        <end-state name='end'/>
      </process-definition>
    ");
		// create the process instance
		$processInstance = new ProcessInstance($processDefinition);
		$this->assertEquals(0, sizeof(EventPropagationTest::$executedActions));
		
		$processInstance->signal();
		
		$this->assertEquals(1, sizeof(EventPropagationTest::$executedActions));
		
		$executedAction = EventPropagationTest::$executedActions[0];
		$this->assertEquals("transition", $executedAction->event->getEventType());
		$this->assertSame($processDefinition, $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->getStartState()->getDefaultLeavingTransition(), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertNull($executedAction->node);
		
		$processInstance->signal();
		
		$this->assertEquals(2, sizeof(EventPropagationTest::$executedActions));
		
		$executedAction = EventPropagationTest::$executedActions[1];
		$this->assertEquals("transition", $executedAction->event->getEventType());
		$this->assertSame($processDefinition, $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->getNode("state")->getDefaultLeavingTransition(), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertNull($executedAction->node);
	}
	public function testNodeToSuperStateEventPropagation() {
		$processDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <start-state name='start'>
          <transition to='superstate/state'/>
        </start-state>
        <super-state name='superstate'>
          <event type='node-enter'>
            <action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Recorder' />
          </event>
          <event type='node-leave'>
            <action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Recorder' />
          </event>
          <state name='state'>
            <transition to='../end'/>
          </state>
        </super-state>
        <end-state name='end'/>
      </process-definition>
    ");
		// create the process instance
		$processInstance = new ProcessInstance($processDefinition);
		$this->assertEquals(0, sizeof(EventPropagationTest::$executedActions));
		
		$processInstance->signal();
		
		$this->assertEquals(1, sizeof(EventPropagationTest::$executedActions));
		
		$executedAction = EventPropagationTest::$executedActions[0];
		$this->assertEquals("node-enter", $executedAction->event->getEventType());
		$this->assertSame($processDefinition->getNode("superstate"), $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->findNode("superstate/state"), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertSame($processDefinition->findNode("superstate/state"), $executedAction->node);
		
		$processInstance->signal();
		
		$this->assertEquals(2, sizeof(EventPropagationTest::$executedActions));
		
		$executedAction = EventPropagationTest::$executedActions[1];
		$this->assertEquals("node-leave", $executedAction->event->getEventType());
		$this->assertSame($processDefinition->getNode("superstate"), $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->findNode("superstate/state"), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertSame($processDefinition->findNode("superstate/state"), $executedAction->node);
	}
	public function testTransitionToSuperStateEventPropagation() {
		$processDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <start-state name='start'>
          <transition to='superstate/state'/>
        </start-state>
        <super-state name='superstate'>
          <event type='transition'>
            <action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Recorder' />
          </event>
          <state name='state'>
            <transition to='../end'/>
            <transition name='loop' to='state'/>
          </state>
        </super-state>
        <end-state name='end'/>
      </process-definition>
    ");
		// create the process instance
		$processInstance = new ProcessInstance($processDefinition);
		$this->assertEquals(0, sizeof(EventPropagationTest::$executedActions));
		
		$processInstance->signal();
		
		$this->assertEquals(0, sizeof(EventPropagationTest::$executedActions));
		
		$processInstance->signal("loop");
		
		$this->assertEquals(1, sizeof(EventPropagationTest::$executedActions));
		
		$executedAction = EventPropagationTest::$executedActions[0];
		$this->assertEquals("transition", $executedAction->event->getEventType());
		$this->assertSame($processDefinition->getNode("superstate"), $executedAction->event->getGraphElement());
		$this->assertSame($processDefinition->findNode("superstate/state")->getLeavingTransition("loop"), $executedAction->eventSource);
		$this->assertSame($processInstance->getRootToken(), $executedAction->token);
		$this->assertNull($executedAction->node);
		
		$processInstance->signal();
		
		$this->assertEquals(1, sizeof(EventPropagationTest::$executedActions));
	}
	public function testStraightThrough() {
		$processDefinition = ProcessDefinition::parseXmlString("
      <process-definition> 
        <start-state> 
          <transition to='phase one/a' /> 
        </start-state> 
        <super-state name='phase one'> 
          <event type='transition'> 
            <action ref-name='print action' accept-propagated-events='false'/> 
          </event> 
          <state name='a'> 
            <transition to='b' /> 
          </state> 
          <state name='b'> 
            <transition to='/phase two/c' /> 
            <transition name='back' to='a' /> 
          </state> 
          <transition name='cancel' to='cancelled' /> 
        </super-state> 
        <super-state name='phase two'> 
          <state name='c'> 
            <transition to='d' /> 
          </state> 
          <state name='d'> 
            <transition to='/end' /> 
          </state> 
        </super-state> 
        <end-state name='end' /> 
        <end-state name='cancelled' /> 
        <action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\PrintMessage' />
      </process-definition>");
		
		$a = $processDefinition->findNode("phase one/a");
		$b = $processDefinition->findNode("phase one/b");
		$c = $processDefinition->findNode("phase two/c");
		$d = $processDefinition->findNode("phase two/d");
		$end = $processDefinition->getNode("end");
		
		// starting a new $process instance
		$processInstance = new ProcessInstance($processDefinition);
		$token = $processInstance->getRootToken();
		
		$processInstance->signal();
		$this->assertEquals($a, $token->getNode());
		$processInstance->signal();
		$this->assertEquals($b, $token->getNode());
		$processInstance->signal("back");
		$this->assertEquals($a, $token->getNode());
		$processInstance->signal();
		$this->assertEquals($b, $token->getNode());
		$processInstance->signal();
		$this->assertEquals($c, $token->getNode());
		$processInstance->signal();
		$this->assertEquals($d, $token->getNode());
		$processInstance->signal();
		$this->assertEquals($end, $token->getNode());
		$this->assertEquals(0, EventPropagationTest::$nbrOfMessagesPrinted);
	}
}
class ExecutedAction {
	// ExectionContext members
	public $token = null;
	public $event = null;
	public $eventSource = null;
	public $action = null;
	public $exception = null;
	// The node returned by the ExecutionContext at the time of execution
	public $node = null;
}
class Recorder implements ActionHandler {
	public function execute(ExecutionContext $executionContext) {
		$executedAction = new ExecutedAction();
		$executedAction->token = $executionContext->getToken();
		$executedAction->event = $executionContext->getEvent();
		$executedAction->eventSource = $executionContext->getEventSource();
		$executedAction->action = $executionContext->getAction();
		$executedAction->exception = $executionContext->getException();
		$executedAction->node = $executionContext->getNode();
		EventPropagationTest::$executedActions[] = $executedAction;
	}
}
class PrintMessage implements ActionHandler {
	public function execute(ExecutionContext $executionContext) {
		EventPropagationTest::$nbrOfMessagesPrinted++;
	}
}

