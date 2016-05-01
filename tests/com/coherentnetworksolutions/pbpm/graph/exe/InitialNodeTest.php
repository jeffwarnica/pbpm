<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;

class InitialNodeTest extends \PHPUnit_Framework_TestCase {

  public function testInitialNode() {
    $processDefinition = ProcessDefinition::parseXmlString("
      <process-definition initial='first'>
        <state name='first'/>
      </process-definition>"
    );
    
    $this->assertEquals("first", $processDefinition->getStartState()->getName());
    $processInstance = new ProcessInstance($processDefinition);
    $this->assertEquals("first", $processInstance->getRootToken()->getNode()->getName());
  }
  
  public function testInitialNodeExecution() {
    $processDefinition = ProcessDefinition::parseXmlString("
      <process-definition initial='first'>
        <node name='first'>
          <transition to='next'/>
        </node>
        <state name='next'>
        </state>
      </process-definition>"
    );
    
    $this->assertEquals("first", $processDefinition->getStartState()->getName());
    $processInstance = new ProcessInstance($processDefinition);
    $this->assertEquals("next", $processInstance->getRootToken()->getNode()->getName());
  }

  public function testStartState() {
    $processDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <start-state name='first'>
          <transition to='next'/>
        </start-state >
        <state name='next'>
        </state>
      </process-definition>"
    );
    
    $this->assertEquals("first", $processDefinition->getStartState()->getName());
    $processInstance = new ProcessInstance($processDefinition);
    $this->assertEquals("first", $processInstance->getRootToken()->getNode()->getName());
  }
}
