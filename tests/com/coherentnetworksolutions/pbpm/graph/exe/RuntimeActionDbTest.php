<?php
namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class RuntimeActionsDbTest extends AbstractDbTestCase {

  public function testRuntimeActionEvent() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='{$this->getName()}'>
      <event type='process-start' />
      <action name='gotocheetahs' class='com.secret.LetsDoItSneeky'/>
      </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $event = $processInstance->getProcessDefinition()->getEvent("process-start");
    $action = $processInstance->getProcessDefinition()->getAction("gotocheetahs");
    $processInstance->addRuntimeAction(new RuntimeAction($event, $action));

    $processInstance = $this->saveAndReload($processInstance);
    $runtimeAction = $processInstance->getRuntimeActions()->get(0);
    $event = $processInstance->getProcessDefinition()->getEvent("process-start");
    $this->assertEquals($event->getGraphElement(), $runtimeAction->getGraphElement());
    $this->assertEquals($event->getEventType(), $runtimeAction->getEventType());
  }

  public function testRuntimeActionAction() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='{$this->getName()}'>
        <event type='process-start' />
        <action name='gotocheetahs' class='com.secret.LetsDoItSneeky'/>
      </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $event = $processInstance->getProcessDefinition()->getEvent("process-start");
    $action = $processInstance->getProcessDefinition()->getAction("gotocheetahs");
    $processInstance->addRuntimeAction(new RuntimeAction($event, $action));

    $processInstance = $this->saveAndReload($processInstance);
    $runtimeAction = $processInstance->getRuntimeActions()->get(0);
    $action = $processInstance->getProcessDefinition()->getAction("gotocheetahs");
    $this->assertSame($action, $runtimeAction->getAction());
  }

  public function testRuntimeActionOnNonExistingEvent() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='{$this->getName()}'>
        <action name='gotocheetahs' class='com->secret->LetsDoItSneeky'/>
      </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $action = $processInstance->getProcessDefinition()->getAction("gotocheetahs");
    $processInstance->addRuntimeAction(new RuntimeAction($processDefinition, $action, "process-start"));

    $processInstance = $this->saveAndReload($processInstance);
    $runtimeAction = $processInstance->getRuntimeActions()->get(0);
    $action = $processInstance->getProcessDefinition()->getAction("gotocheetahs");
    $this->assertSame($action, $runtimeAction->getAction());
  }

}
