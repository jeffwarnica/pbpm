<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\scheduler\def\CreateTimerAction;

class TimerValidatingTest extends \PHPUnit_Framework_TestCase {
    private $schemaNamespace = "xmlns=\"http://jbpm.org/3/jpdl\"";
    
    /**
     * @test
     */
    public function testTimerCreateAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace}>
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
//         $this->assertEquals("the-remainder-action-class-name", $createTimerAction->getTimerAction()->getActionDelegation()->getClassName());
    }
    
    /**
     * @test
     */
    public function testTimerDefaultName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace}>
                          <node name='catch crooks'>
                            <timer duedate='5 days and 10 minutes and 3 seconds'>
                               <script/>
                            </timer>
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
                        "<process-definition {$this->schemaNamespace}>
                          <node name='catch crooks'>
                            <timer duedate='5 minutes and 1 second'><script/></timer>
                          </node>
                        </process-definition>"
        );
    
        
        $cancelTimerAction = $processDefinition->getNode("catch crooks")->getEvent("node-enter")->getActions()[0];
        $this->assertEquals("catch crooks", $cancelTimerAction->getTimerName());
    }
    
//     /**
//        @todo: script
//      * @test
//      */
//     public function testTimerScript() {
//         $processDefinition = ProcessDefinition::parseXmlString(
//                         "<process-definition {$this->schemaNamespace}>
//                           <node name='catch crooks'>
//                             <timer duedate='1 day'>
//                               <script />
//                             </timer>
//                           </node>
//                         </process-definition>"
//         );
    
//         $createTimerAction = $processDefinition->getNode("catch crooks")->getEvent("node-enter")->getActions()[0];
//         $timerAction = $createTimerAction->getTimerAction();
//         $this->assertEquals("", get_class($createTimerAction->getTimerAction()));
//     }
    
    /**
     * @test
     */
    public function testCreateTimerAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace}>
                          <node name='catch crooks xxx'>
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
    
        /**
         * @var CreateTimerAction
         */
        $createTimerAction = $processDefinition->getNode("catch crooks xxx")->getEvent("node-enter")->getActions()[0];
    
        $this->assertEquals("reminder", $createTimerAction->getTimerName());
        $this->assertEquals("2 business hours", $createTimerAction->getDueDate());
        $this->assertEquals("10 business minutes", $createTimerAction->getRepeat());
        $this->assertEquals("time-out-transition", $createTimerAction->getTransitionName());
        //@todo ActionDelegation
        $this->assertEquals("the-remainder-action-class-name", $createTimerAction->getTimerAction()->getActionDelegation()->getClassName());
    }
    
    /**
     * @test
     */
    public function testCancelTimerAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace}>
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
}