<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

class NodeTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
  public function testNameChange() {
    $node = new Node();
    $this->assertNull($node->getName());
    $node->setName("jos");
    $this->assertEquals("jos", $node->getName());
    $node->setName("piet");
    $this->assertEquals("piet", $node->getName());
  }
  /**
   * @test
   */
  public function testNameChangeInProcessDefinition() {
    $node = new Node();
    $processDefinition = new ProcessDefinition();
    $processDefinition->addNode($node);
    
    $this->assertSame($node, $processDefinition->getNode(null));
    $node->setName("jos");
    $this->assertNull($processDefinition->getNode(null));
    $this->assertSame($node, $processDefinition->getNode("jos"));
    $this->assertEquals("jos", $node->getName());
    $node->setName("piet");
    $this->assertNull($processDefinition->getNode(null));
    $this->assertNull($processDefinition->getNode("jos"));
    $this->assertSame($node, $processDefinition->getNode("piet"));
  }
}