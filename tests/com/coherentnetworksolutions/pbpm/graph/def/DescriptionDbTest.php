<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class DescriptionDbTest extends AbstractDbTestCase {
	
  public function testProcessDefinitionDescription() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <description>haleluja</description>
      </process-definition>");

    $processDefinition = $this->saveAndReload($processDefinition);
    $this->assertEquals("haleluja", $processDefinition->getDescription());
  }

  public function testNodeDescription() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <node name='a'>
          <description>haleluja</description>
        </node>
      </process-definition>");

    $processDefinition = $this->saveAndReload($processDefinition);
    $this->assertEquals("haleluja", $processDefinition->getNode("a")->getDescription());
  }

  public function testTransitionDescription() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <node name='a'>
          <transition name='self' to='a'>
            <description>haleluja</description>
          </transition>
        </node>
      </process-definition>");

    $processDefinition = $this->saveAndReload($processDefinition);
    $this->assertEquals("haleluja", $processDefinition->getNode("a")->getLeavingTransition("self")->getDescription());
  }

  public function testTaskDescription() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <task-node name='a'>
          <task name='self'>
            <description>haleluja</description>
          </task>
        </task-node>
      </process-definition>");
    $taskNode = $processDefinition->getNode("a");

    $processDefinition = $this->saveAndReload($processDefinition);
    $this->assertEquals("haleluja", $taskNode->getTask("self")->getDescription());
  }
}
