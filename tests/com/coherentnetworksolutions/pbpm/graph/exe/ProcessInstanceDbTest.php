<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;
use com\coherentnetworksolutions\pbpm\context\exe\ContextInstance;
use com\coherentnetworksolutions\pbpm\taskmgmt\exe\TaskMgmtInstance;

class ProcessInstanceDbTest extends AbstractDbTestCase {

  public function testProcessInstanceProcessDefinition() {
    $processDefinition = new ProcessDefinition($this->getName());
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);

    $processInstance = $this->saveAndReload($processInstance);
    $processDefinition = $processInstance->getProcessDefinition();
    $this->assertEquals($this->getName(), $processDefinition->getName());
  }

  public function testProcessInstanceDates() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='{$this->getName()}'>
    	<start-state>
          <transition to='end' />
        </start-state>
        <end-state name='end'/>
      </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $processInstance->signal();

    $processInstance = $this->saveAndReload($processInstance);
    $this->assertNotNull($processInstance->getStart());
    $this->assertNotNull($processInstance->getEnd());
  }

  public function testProcessInstanceRootToken() {
    $processDefinition = new ProcessDefinition($this->getName());
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);

    $processInstance = $this->saveAndReload($processInstance);
    $this->assertNotNull($processInstance->getRootToken());
  }

  public function testProcessInstanceSuperProcessToken() {
    $superProcessDefinition = new ProcessDefinition("super");
    $this->deployProcessDefinition($superProcessDefinition);

    $subProcessDefinition = new ProcessDefinition("sub");
    $this->deployProcessDefinition($subProcessDefinition);

    $superProcessInstance = new ProcessInstance($superProcessDefinition);
    $processInstance = new ProcessInstance($subProcessDefinition);
    $superProcessToken = $superProcessInstance->getRootToken();
    $processInstance->setSuperProcessToken($superProcessToken);
    $this->pbpmContext->save($superProcessInstance);

    $processInstance = $this->saveAndReload($processInstance);
    $superProcessToken = $processInstance->getSuperProcessToken();
    $this->assertNotNull($superProcessToken);
    $superProcessInstance = $superProcessToken->getProcessInstance();
    $this->assertNotNull($superProcessInstance);

    $processDefinition = $superProcessInstance->getProcessDefinition();
    $this->assertEquals("super", $processDefinition->getName());
  }

  public function testProcessInstanceModuleInstances() {
    $processDefinition = new ProcessDefinition("modinst");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $processInstance->addInstance(new ContextInstance());
    $processInstance->addInstance(new TaskMgmtInstance());

    $processInstance = $this->saveAndReload($processInstance);
    $this->assertNotNull($processInstance->getInstances());
    $this->assertEquals(2, sizeof($processInstance->getInstances()));
    $this->assertNotNull($processInstance->getContextInstance());
    $this->assertNotNull($processInstance->getTaskMgmtInstance());
  }

  public function testProcessInstanceRuntimeActions() {
    $processDefinition = new ProcessDefinition("modinst");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $processInstance->addRuntimeAction(new RuntimeAction());
    $processInstance->addRuntimeAction(new RuntimeAction());
    $processInstance->addRuntimeAction(new RuntimeAction());
    $processInstance->addRuntimeAction(new RuntimeAction());

    $processInstance = $this->saveAndReload($processInstance);
    $this->assertNotNull($processInstance->getRuntimeActions());
    $this->assertEquals(4, sizeof($processInstance->getRuntimeActions()));
  }
}
