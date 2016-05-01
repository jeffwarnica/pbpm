<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class StateDbTest extends AbstractDbTestCase {

  public function testDbState()  {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='{$this->getName()}'>
        <start-state name='zero'>
          <transition to='one' />
        </start-state>
        <state name='one'>
          <transition to='two' />
        </state>
        <state name='two'>
          <transition to='three' />
        </state>
        <state name='three'>
          <transition to='end' />
        </state>
        <end-state name='end' />
      </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $this->assertEquals("zero", $processInstance->getRootToken()->getNode()->getName());

    $processInstance = $this->saveAndReload($processInstance);
    $processInstance->signal();
    $this->assertEquals("one", $processInstance->getRootToken()->getNode()->getName());

    $processInstance = $this->saveAndReload($processInstance);
    $processInstance->signal();
    $this->assertEquals("two", $processInstance->getRootToken()->getNode()->getName());

    $processInstance = $this->saveAndReload($processInstance);
    $processInstance->signal();
    $this->assertEquals("three", $processInstance->getRootToken()->getNode()->getName());

    $processInstance = $this->saveAndReload($processInstance);
    $processInstance->signal();
    $this->assertEquals("end", $processInstance->getRootToken()->getNode()->getName());
  }
}
