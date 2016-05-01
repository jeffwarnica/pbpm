<?php 
namespace com\coherentnetworksolutions\pbpm\taskmgmt\def;

use com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskMgmtDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class SwimlaneDbTest extends AbstractDbTestCase {

	/**
	 * @var ProcessDefinition $processDefinition
	 */
  private $processDefinition;
  /**
   * @var TaskMgmtDefinition $taskMgmtDefinition
   */
  private $taskMgmtDefinition;
  
 /**
  * @var Swimlane $buyer
  */
  private $buyer;
  /**
   * @var Task $laundry
   */
  private $laundry;
  /**
   * @var Task $dishes
   */
  private $dishes;
  
  /**
   * @before
   */
  public function setupStuff() {
  	$this->taskMgmtDefinition = new TaskMgmtDefinition();
  	$this->processDefinition = new ProcessDefinition();//"My First Process Definition");
  	$this->processDefinition->addDefinition($this->taskMgmtDefinition);
  	$this->buyer = new Swimlane("buyer");
  	$this->laundry = new Task("laundry");
  	$this->dishes = new Task("dishes");
  }
  

  /**
   * @test
   */
  public function testSwimlaneAddTask() {
    $this->buyer->addTask($this->laundry);
    $this->buyer->addTask($this->dishes);

    $this->processDefinition = $this->saveAndReload($this->processDefinition);
    $this->taskMgmtDefinition = $this->processDefinition->getTaskMgmtDefinition();

    $this->assertEquals(2, sizeof($this->buyer->getTasks()));
    $this->assertTrue($this->buyer->getTasks()->contains($this->laundry));
    $this->assertTrue($this->buyer->getTasks()->contains($this->dishes));
  }

  /**
   * @test
   */
  public function testSwimlaneAddTaskInverseReference() {
    $this->buyer->addTask($this->laundry);
    $this->buyer->addTask($this->dishes);

    $this->processDefinition = $this->saveAndReload($this->processDefinition);
    $this->taskMgmtDefinition = $this->processDefinition->getTaskMgmtDefinition();

    $this->assertSame($this->buyer, $this->laundry->getSwimlane());
    $this->assertSame($this->buyer, $this->dishes->getSwimlane());
  }

  /**
   * @test
   */
  public function testTriangularRelation() {
  	
  	$this->buyer->addTask($this->laundry);
	$this->taskMgmtDefinition->addTask($this->laundry);
	$this->taskMgmtDefinition->addSwimlane($this->buyer);
	
  	$this->processDefinition = $this->saveAndReload($this->processDefinition);
  	$this->taskMgmtDefinition = $this->processDefinition->getTaskMgmtDefinition();
  	
  	/**
  	 * @var Task $laundry
  	 */
    $laundry = $this->taskMgmtDefinition->getTask("laundry");
    
    $this->assertEquals(1, sizeof($this->taskMgmtDefinition->getTasks()));
    $this->assertEquals(1, sizeof($this->buyer->getTasks()));
    $this->assertEquals("laundry", $laundry->getName());
    $this->assertSame($laundry, $this->taskMgmtDefinition->getSwimlane("buyer")->getTasks()[0]);
    $this->assertSame($this->taskMgmtDefinition, $this->laundry->getTaskMgmtDefinition());
    $this->assertSame($this->taskMgmtDefinition->getSwimlane("buyer"), $laundry->getSwimlane());
  }

  /**
   * @test
   */
  public function testSwimlaneAssignment() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>" .
      "  <swimlane name='boss'>" .
      "    <assignment class='org.jbpm.TheOneAndOnly' />" .
      "  </swimlane>" .
      "</process-definition>");

    $processDefinition = $this->saveAndReload($processDefinition);
    $this->taskMgmtDefinition = $processDefinition->getTaskMgmtDefinition();

    /**
     * @var Swimlane
     */
    $boss = $this->taskMgmtDefinition->getSwimlane("boss");
    $this->assertNotNull($boss);
    $bossAssignmentDelegation = $boss->getAssignmentDelegation();
    $bossAssignmentDelegationClassName = $bossAssignmentDelegation->getClassName();
    $this->assertNotNull($bossAssignmentDelegation);
    $this->assertNotNull($bossAssignmentDelegationClassName);
    $this->assertEquals("org.jbpm.TheOneAndOnly", $bossAssignmentDelegationClassName);
  }

  /**
   * @test
   */
  public function testSwimlaneTaskMgmtTest() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>" .
      "  <swimlane name='boss'>" .
      "    <assignment class='org.jbpm.TheOneAndOnly' />" .
      "  </swimlane>" .
      "</process-definition>");

    $processDefinition = $this->saveAndReload($processDefinition);
    $taskMgmtDefinition = $processDefinition->getTaskMgmtDefinition();

    $boss = $taskMgmtDefinition->getSwimlane("boss");
    $this->assertNotNull($boss);
    $this->assertSame($taskMgmtDefinition, $boss->getTaskMgmtDefinition());
  }

  /**
   * @test
   */
  public function testTaskToSwimlane() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>" .
      "  <swimlane name='boss'>" .
      "    <assignment class='org.jbpm.TheOneAndOnly' />" .
      "  </swimlane>" .
      "  <task-node name='work'>" .
      "    <task name='manage' swimlane='boss' description='fxxxxx' />" .
      "  </task-node>" .
      "</process-definition>");

    $processDefinition = $this->saveAndReload($processDefinition);
    $taskMgmtDefinition = $processDefinition->getTaskMgmtDefinition();

    $work = $processDefinition->getNode("work");
    $manage = $work->getTask("manage");
    $this->assertNotNull($manage);
    $this->assertSame($taskMgmtDefinition->getTask("manage"), $manage);

    $this->assertNotNull($manage->getSwimlane());
    $this->assertSame($taskMgmtDefinition->getSwimlane("boss"), $manage->getSwimlane());
  }
}
