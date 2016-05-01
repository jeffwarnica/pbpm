<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\context\def\Access;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\node\TaskNode;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Task;

class TimerXmlTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function testTimerCreateAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='catch crooks'>
                            <timer name='reminder' 
                                   duedate='2 business hours' 
                                   repeat='10 business minutes'
                                   transition='time-out-transition' >
                              <action class='the-remainder-action-class-name' />
                            </timer>
                          </node>
                        </process-definition>"
        );
    
        $createTimerAction = $processDefinition->getNode("catch crooks")->getEvent("node-enter")->getActions()[0];
    
        $this->assertEquals("reminder", $createTimerAction->getTimerName());
        $this->assertEquals("2 business hours", $createTimerAction->getDueDate());
        $this->assertEquals("10 business minutes", $createTimerAction->getRepeat());
        $this->assertEquals("time-out-transition", $createTimerAction->getTransitionName());
//         $this->assertEquals("the-remainder-action-class-name", $createTimerAction.getTimerAction().getActionDelegation().getClassName());
    }
    
    /**
     * @test
     */
     public function testTimerDefaultName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='catch crooks'>
                            <timer />
                          </node>
                        </process-definition>"
        );
    
        $createTimerAction = $processDefinition->getNode("catch crooks")->getEvent("node-enter")->getActions()[0];
    
        $this->assertEquals("catch crooks", $createTimerAction->getTimerName());
    }
    
    /**
     * @test
     */
    public function testTimerCancelAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='catch crooks'>
                            <timer />
                          </node>
                        </process-definition>"
        );
    
        $cancelTimerAction = $processDefinition->getNode("catch crooks")->getEvent("node-leave")->getActions()[0];
    
        $this->assertEquals("catch crooks", $cancelTimerAction->getTimerName());
    }
    
    /**
     * @test
     */
    public function  testCreateTimerAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='catch crooks'>
                            <event type='node-enter'>
                              <create-timer name='reminder' 
                                            duedate='2 business hours' 
                                            repeat='10 business minutes'
                                            transition='time-out-transition' >
                                <action class='the-remainder-action-class-name' />
                              </create-timer>
                            </event>
                          </node>
                        </process-definition>"
        );
    
        $createTimerAction = $processDefinition->getNode("catch crooks")->getEvent("node-enter")->getActions()[0];
    
        $this->assertEquals("reminder", $createTimerAction->getTimerName());
        $this->assertEquals("2 business hours", $createTimerAction->getDueDate());
        $this->assertEquals("10 business minutes", $createTimerAction->getRepeat());
        $this->assertEquals("time-out-transition", $createTimerAction->getTransitionName());
//         $this->assertEquals("the-remainder-action-class-name", $createTimerAction->getTimerAction()->getActionDelegation().getClassName());
    }
    
    /**
     * @read
     */
    public function testCancelTimerAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='catch crooks'>
                            <event type='node-enter'>
                              <cancel-timer name='reminder' />
                            </event>
                          </node>
                        </process-definition>"
        );
    
        $cancelTimerAction = $processDefinition->getNode("catch crooks")->getEvent("node-enter")->getActions()[0];
        $this->assertEquals("reminder", $cancelTimerAction->getTimerName());
    }

    //@todo script
//     public void testTimerScript() {
//         ProcessDefinition processDefinition = ProcessDefinition.parseXmlString(
//                         "<process-definition>
//                         "  <node name='catch crooks'>
//                         "    <timer>
//                         "      <script />
//                         "    </timer>
//                         "  </node>
//                         "</process-definition>"
//         );
    
//         CreateTimerAction createTimerAction =
//         (CreateTimerAction) processDefinition
//         .getNode("catch crooks")
//         .getEvent("node-enter")
//         .getActions()
//         .get(0);
    
//         assertEquals(Script.class, createTimerAction.getTimerAction().getClass());
//     }
}
