<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\context\def\Access;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\node\TaskNode;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Task;

class TaskNodeXmlTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function testTaskNodeName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                            <task-node name='t'>
                            </task-node>
                        </process-definition>");
        $taskNode = $processDefinition->getNode("t");
        $this->assertNotNull($taskNode);
        $this->assertEquals("t", $taskNode->getName());
    }

    /**
     * @test
     */
    public function testTaskNodeTasks() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <task-node name='t'>
                            <task name='one' />
                            <task name='two' />
                            <task name='three' />
                          </task-node>
                        </process-definition>");
        $taskNode = $processDefinition->getNode("t");
        $this->assertNotNull($taskNode);
        $this->assertEquals(3, sizeof($taskNode->getTasks()));
    }

    /**
     * @test
     */
    public function testTaskNodeDefaultSignal() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <task-node name='t' />
                        </process-definition>");
        $taskNode = $processDefinition->getNode("t");
        
        $this->assertEquals(TaskNode::$SIGNAL_LAST, $taskNode->getSignal());
    }

    /**
     * @test
     */
    public function testTaskNodeSignalFirst() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <task-node name='t' signal='first' />
                        </process-definition>");
        $taskNode = $processDefinition->getNode("t");
        $this->assertEquals(TaskNode::$SIGNAL_FIRST, $taskNode->getSignal());
    }

    /**
     * @test
     */
    public function testTaskNodeDefaultCreate() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='t' />
                            </process-definition>");
        $taskNode = $processDefinition->getNode("t");
        $this->assertTrue($taskNode->getCreateTasks());
    }

    /**
     * @test
     */
    public function testTaskNodeNoCreate() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='t' create-tasks='false'/>
                            </process-definition>");
        $taskNode = $processDefinition->getNode("t");
        $this->assertFalse($taskNode->getCreateTasks());
    }
    
    //     /**
    //      * @test
    // @todo swimlane
    //      */
    //     public function testSwimlane() {
    //         $processDefinition = ProcessDefinition::parseXmlString(
    //                         "<process-definition>
    //                               <swimlane name='initiator'>
    //                                 <assignment class='assignment-specified-just-to-prevent-a-warning'/>
    //                               </swimlane>
    //                             </process-definition>");
    //         $taskMgmtDefinition = $processDefinition->getTaskMgmtDefinition();
    //         $initiatorSwimlane = $taskMgmtDefinition->getSwimlane("initiator");
    //         $this->assertNotNull($initiatorSwimlane);
    //         $this->assertEquals("initiator", $initiatorSwimlane->getName());
    //     }
    

    //     /**
    //      * @test
    //      */
    //@todo swimlane
    //     public function testTaskSwimlane() {
    //         $processDefinition = ProcessDefinition::parseXmlString(
    //                         "<process-definition>
    //                               <swimlane name='initiator'>
    //                                 <assignment class='assignment-specified-just-to-prevent-a-warning'/>
    //                               </swimlane>
    //                               <task name='grow old' swimlane='initiator' />
    //                             </process-definition>");
    //         $growOld = $processDefinition->getTaskMgmtDefinition()->getTask("grow old");
    //         $this->assertNotNull($growOld);
    //         $this->assertNotNull($growOld->getSwimlane());
    //         $this->assertEquals("initiator", $growOld->getSwimlane()->getName());
    //     }
    

    /**
     * @test
     */
    public function testTaskCreationEvent() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='a'>
                                <task name='clean ceiling'>
                                  <event type='task-create'>
                                    <action class='org.jbpm.taskmgmt.exe.TaskEventTestXPlusPlus' />
                                  </event>
                                </task>
                              </task-node>
                            </process-definition>");
        $taskNode = $processDefinition->getNode("a");
        
        $task = $taskNode->getTask("clean ceiling");
        $this->assertNotNull($task->getEvent(Event::EVENTTYPE_TASK_CREATE));
    }

    /**
     * @test
     */
    public function testTaskStartEvent() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='a'>
                                <task name='clean ceiling'>
                                  <event type='task-start'>
                                    <action class='org.jbpm.taskmgmt.exe.TaskEventTestXPlusPlus' />
                                  </event>
                                </task>
                              </task-node>
                            </process-definition>");
        $taskNode = $processDefinition->getNode("a");
        $task = $taskNode->getTask("clean ceiling");
        $this->assertNotNull($task->getEvent(Event::EVENTTYPE_TASK_START));
    }

    /**
     * @test
     */
    public function testTaskAssignEvent() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='a'>
                                <task name='clean ceiling'>
                                  <event type='task-assign'>
                                    <action class='org.jbpm.taskmgmt.exe.TaskEventTestXPlusPlus' />
                                  </event>
                                </task>
                              </task-node>
                            </process-definition>");
        $taskNode = $processDefinition->getNode("a");
        $task = $taskNode->getTask("clean ceiling");
        $this->assertNotNull($task->getEvent(Event::EVENTTYPE_TASK_ASSIGN));
    }

    /**
     * @test
     */
    public function testTaskEndEvent() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='a'>
                                <task name='clean ceiling'>
                                  <event type='task-end'>
                                    <action class='org.jbpm.taskmgmt.exe.TaskEventTestXPlusPlus' />
                                  </event>
                                </task>
                              </task-node>
                            </process-definition>");
        $taskNode = $processDefinition->getNode("a");
        $task = $taskNode->getTask("clean ceiling");
        $this->assertNotNull($task->getEvent(Event::EVENTTYPE_TASK_END));
    }

    /**
     * @test
     */
    public function testTaskTimer() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='a'>
                                <task name='clean ceiling'>
                                  <timer duedate='2 business minutes'>
                                    <action class='org.jbpm.taskmgmt.exe.TaskEventTestXPlusPlus' />
                                  </timer>
                                </task>
                              </task-node>
                            </process-definition>");
        $taskNode = $processDefinition->getNode("a");
        $task = $taskNode->getTask("clean ceiling");
        $event = $task->getEvent(Event::EVENTTYPE_TASK_CREATE);
        $this->assertNotNull($event);
        $createTimerAction = $event->getActions()[0];
        $this->assertNotNull($createTimerAction);
        $this->assertEquals("2 business minutes", $createTimerAction->getDueDate());
        
        // test default cancel event
        $event = $task->getEvent(Event::EVENTTYPE_TASK_END);
        $this->assertNotNull($event);
        $cancelTimerAction = $event->getActions()[0];
        $this->assertNotNull($cancelTimerAction);
        
    }

    /**
     * @test
     */
    public function testTaskTimerCancelEvents() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <task-node name='a'>
                                <task name='clean ceiling'>
                                  <timer duedate='2 business minutes' cancel-event='task-start, task-assign, task-end'>
                                    <action class='org.jbpm.taskmgmt.exe.TaskEventTestXPlusPlus' />
                                  </timer>
                                </task>
                              </task-node>
                            </process-definition>");
        $taskNode = $processDefinition->getNode("a");
        $task = $taskNode->getTask("clean ceiling");
        
        $event = $task->getEvent(Event::EVENTTYPE_TASK_CREATE);
        $this->assertNotNull($event);
        $this->assertSame('com\coherentnetworksolutions\pbpm\scheduler\def\CreateTimerAction', get_class($event->getActions()[0]));
        
        $event = $task->getEvent(Event::EVENTTYPE_TASK_START);
        $this->assertNotNull($event);
        $this->assertSame('com\coherentnetworksolutions\pbpm\scheduler\def\CancelTimerAction', get_class($event->getActions()[0]));

        $event = $task->getEvent(Event::EVENTTYPE_TASK_ASSIGN);
        $this->assertNotNull($event);
        $this->assertSame('com\coherentnetworksolutions\pbpm\scheduler\def\CancelTimerAction', get_class($event->getActions()[0]));

        $event = $task->getEvent(Event::EVENTTYPE_TASK_END);
        $this->assertNotNull($event);
        $this->assertSame('com\coherentnetworksolutions\pbpm\scheduler\def\CancelTimerAction', get_class($event->getActions()[0]));
    }
}