<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class TokenDbTest extends AbstractDbTestCase {

  public function testTokenName() {
    $processDefinition = ProcessDefinition::parseXmlString("
    <process-definition name='{$this->getName()}'>
         <start-state />
       </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $processInstance->getRootToken()->setName("roottoken");

    $processInstance = $this->saveAndReload($processInstance);
    $this->assertEquals("roottoken", $processInstance->getRootToken()->getName());
  }

  public function testTokenStartAndEndDate() {
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
    $token = $processInstance->getRootToken();
    $this->assertNotNull($token->getStart());
    $this->assertNotNull($token->getEnd());
  }

  public function testTokenNode() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='{$this->getName()}'>
        <start-state name='s' />
      </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);

    $processInstance = $this->saveAndReload($processInstance);
    $s = $processInstance->getProcessDefinition()->getStartState();
    $this->assertSame($s, $processInstance->getRootToken()->getNode());
  }

  public function testTokenProcessInstance() {
    $processDefinition = new ProcessDefinition($this->getName());
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);

    $this->processInstance = $this->saveAndReload($processInstance);
    $this->assertSame($processInstance, $processInstance->getRootToken()->getProcessInstance());
  }

  public function testTokenParent() {
    $processDefinition = new ProcessDefinition($this->getName());
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);

    $this->processInstance = $this->saveAndReload($processInstance);  	
    new Token($processInstance->getRootToken(), "one");

    $processInstance = $this->saveAndReload($processInstance);
    $rootToken = $processInstance->getRootToken();
    $childOne = $rootToken->getChild("one");
    $this->assertSame($rootToken, $childOne->getParent());
  }

  public function testTokenChildren() {
    $processDefinition = new ProcessDefinition($this->getName());
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
  	
    new Token($processInstance->getRootToken(), "one");
    new Token($processInstance->getRootToken(), "two");
    new Token($processInstance->getRootToken(), "three");

    $processInstance = $this->saveAndReload($processInstance);
    $rootToken = $processInstance->getRootToken();
    $childOne = $rootToken->getChild("one");
    $childTwo = $rootToken->getChild("two");
    $childThree = $rootToken->getChild("three");

    $this->assertEquals("one", $childOne->getName());
    $this->assertEquals("two", $childTwo->getName());
    $this->assertEquals("three", $childThree->getName());
  }

  /**
   * @test
   * @requires function NEEDS_CONDITIONS_IMPLEMENTED
   */
  public function testAvailableTransitions() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='conditionsprocess'>
        <start-state name='zero'>
          <transition to='one'   condition='#{a==5}' />
          <transition to='two'   condition='#{a&gt;7}' />
          <transition to='three' />
          <transition to='four'  condition='#{a&lt;7}' />
        </start-state>
        <state name='one' />
        <state name='two' />
        <state name='three' /> 
        <state name='four' />
      </process-definition>");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = $this->pbpmContext->newProcessInstance("conditionsprocess");
    $processInstance->getContextInstance()->setVariable("a", 5);
    $processInstance = $this->saveAndReload($processInstance);

    $availableTransitions = $processInstance->getRootToken()->getAvailableTransitions();
    for ($iter = $availableTransitions->getIterator(); $iter->hasNext();) {
      $transition = $iter->next();
      $availableToNames[] = $transition->getTo()>getName();
    }

//     Set expectedToNames = new HashSet();
//     expectedToNames.add("one");
//     expectedToNames.add("three");
//     expectedToNames.add("four");

//     assertEquals(expectedToNames, availableToNames);
  }
}
