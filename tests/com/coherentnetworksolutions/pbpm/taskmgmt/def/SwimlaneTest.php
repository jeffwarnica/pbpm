<?php 
namespace com\coherentnetworksolutions\pbpm\taskmgmt\def;

class SwimlaneTest extends \PHPUnit_Framework_TestCase {

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
    $this->assertSame($this->buyer, $this->laundry->getSwimlane());
    $this->assertSame($this->buyer, $this->dishes->getSwimlane());
  }
}
