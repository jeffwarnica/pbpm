<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Task;
use com\coherentnetworksolutions\pbpm\context\def\Access;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskController;

class TaskControllerXmlTest extends \PHPUnit_Framework_TestCase {
    /**
     * @test
     */
    public function testTaskControllerWithVariableAccesses() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>" .
                        "  <task-node name='t'>" .
                        "    <task name='clean ceiling'>" . 
                        "      <controller>" .
                        "        <variable name='a' access='read,write' mapped-name='x' />" .
                        "        <variable name='b' access='read,write' mapped-name='y' />" .
                        "        <variable name='c' access='read,write' />" .
                        "      </controller>" .
                        "    </task>" .
                        "  </task-node>" .
                        "</process-definition>"
        );
        $taskNode =  $processDefinition->getNode("t");
        $task = $taskNode->getTask("clean ceiling");
        $taskController = $task->getTaskController();
        $this->assertNotNull($taskController);
//         $this->assertNull($taskController->getTaskControllerDelegation());
        
        $variableAccesses = $taskController->getVariableAccesses();
        $this->assertNotNull($variableAccesses);
        $this->assertEquals(3, sizeof($variableAccesses));
        $variableAccess = $variableAccesses[0];
        $this->assertNotNull($variableAccesses);
        $this->assertEquals("a", $variableAccess->getVariableName());
        $this->assertEquals(new Access("read,write"), $variableAccess->getAccess());
        $this->assertEquals("x", $variableAccess->getMappedName());
        $variableAccess = $variableAccesses[2];
        $this->assertNotNull($variableAccesses);
        $this->assertEquals("c", $variableAccess->getMappedName());
    }
    
    /**
     * @test
     */
    public function testTaskControllerWithDelegation() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <task-node name='t'>
                            <task name='clean ceiling'>
                              <controller class='my-own-task-controller-handler-class'>
                                --here comes the configuration of the task controller handler--
                              </controller>
                            </task>
                          </task-node>
                        </process-definition>"
        );
        $taskNode = $processDefinition->getNode("t");
        $task = $taskNode->getTask("clean ceiling");
        /**
         * @var TaskController
         */
        $taskController = $task->getTaskController();
        $this->assertTrue(sizeof($taskController->getVariableAccesses()) === 0);
        $taskControllerDelegation = $taskController->getTaskControllerDelegation();
        $this->assertNotNull($taskControllerDelegation);
        $this->assertEquals("my-own-task-controller-handler-class", $taskControllerDelegation->getClassName());
    }
    
    /**
     * @test
     */
    public function testStartTaskController() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>" .
                        "  <start-state name='t'>" .
                        "    <task name='task to start this process'>" .
                        "      <controller />" .
                        "    </task>" .
                        "  </start-state>" .
                        "</process-definition>"
        );
        $task = $processDefinition->getTaskMgmtDefinition()->getStartTask();
        $this->assertNotNull($task->getTaskController());
    }    
}