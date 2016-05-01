<?php
namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\ActionHandler;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class RuntimeActionsTest extends AbstractDbTestCase {
	
	/**
	 * @var ProcessDefinition $processDefinition
	 */
	private $processDefinition;
	
	/**
	 * @before
	 * @see \com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase::setUp()
	 */
	public function blah() {
		$this->processDefinition = ProcessDefinition::parseXmlString("
		    <process-definition>		      
				<start-state name='start'>
		        <transition to='a' />
		      </start-state>
		      <state name='a'>
		        <transition to='a' />
		      </state>
		      <action name='plusplus' class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\RuntimeActionsTestPlusPlus' />
		    </process-definition>"
		  );
		parent::setUp();
	}
	
  public static $count = 0;
  
  public function testRuntimeAction() {
    // start the process instance
    $processInstance = new ProcessInstance($this->processDefinition);
    // make sure node a was entered once before the runtime action was added 
    $processInstance->signal();

    // no action was added on enter of node a yet...
    $this->assertEquals(0,RuntimeActionsTest::$count);

    // add the runtime action on entrance of node a
    $plusplusAction = $this->processDefinition->getAction("plusplus");
    $enterB = new Event(Event::EVENTTYPE_NODE_ENTER);
    $this->processDefinition->getNode("a")->addEvent($enterB);
    $runtimeAction = new RuntimeAction($enterB,$plusplusAction);
    $processInstance->addRuntimeAction($runtimeAction);

    // loop back to node a, firing event node-enter for the second time
    $processInstance->signal();
    
    // only the second time, the counter should have been plusplussed
    $this->assertEquals(1,RuntimeActionsTest::$count);
  }
}

class RuntimeActionsTestPlusPlus implements ActionHandler {
	private static $serialVersionUID = 1;
	public function execute(ExecutionContext $executionContext) {
		// increase the static counter of the test class
		RuntimeActionsTest::$count++;
	}
}

