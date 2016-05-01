<?php

namespace com\coherentnetworksolutions\pbpm\graph\node;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class ProcessStateTest extends AbstractDbTestCase {

	/**
	 *@test
	 *
	 */
  public function testBasicScenario() {
    $superProcessDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <start-state>
          <transition to='subprocessnode' />
        </start-state>
        <process-state name='subprocessnode'>
          <transition to='end' />
        </process-state>
        <end-state name='end' />
      </process-definition>" 
    );

    $subProcessDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <start-state>
          <transition to='state' />
        </start-state>
        <state name='state'>
          <transition to='end' />
        </state>
        <end-state name='end' />
      </process-definition>" 
    );
    
    $processState = $superProcessDefinition->getNode("subprocessnode");
    $processState->setSubProcessDefinition($subProcessDefinition);
    
    $superProcessInstance = new ProcessInstance($superProcessDefinition);
    $superProcessInstance->signal();

    $superToken = $superProcessInstance->getRootToken();
//     print "Is [{$processState}] == [{$superToken->getNode()}]?";
    $this->assertSame($processState, $superToken->getNode());
    
    $subProcessInstance = $superToken->getSubProcessInstance();
    $this->assertSame($subProcessDefinition, $subProcessInstance->getProcessDefinition());
    $subToken = $subProcessInstance->getRootToken();

    $this->assertSame($subProcessDefinition->getNode("state"), $subToken->getNode());

    $subToken->signal();

    $this->assertSame($subProcessDefinition->getNode("end"), $subToken->getNode());    
    $this->assertTrue($subToken->hasEnded());
    $this->assertTrue($subProcessInstance->hasEnded());

    $this->assertSame($superProcessDefinition->getNode("end"), $superToken->getNode());    
    $this->assertTrue($superToken->hasEnded());
    $this->assertTrue($superProcessInstance->hasEnded());
  }

//   public void testScenarioWithVariables() {
//     ProcessDefinition superProcessDefinition = ProcessDefinition.parseXmlString(
//       "<process-definition>
//       "  <start-state>
//       "    <transition to='subprocessnode' />
//       "  </start-state>
//       "  <process-state name='subprocessnode'>
//       "    <variable name='a' mapped-name='aa' />
//       "    <variable name='b' mapped-name='bb' />
//       "    <transition to='end' />
//       "  </process-state>
//       "  <end-state name='end' />
//       "</process-definition>" 
//     );
//     superProcessDefinition.addDefinition(new ContextDefinition());

//     ProcessDefinition subProcessDefinition = ProcessDefinition.parseXmlString(
//       "<process-definition>
//       "  <start-state>
//       "    <transition to='state' />
//       "  </start-state>
//       "  <state name='state'>
//       "    <transition to='end' />
//       "  </state>
//       "  <end-state name='end' />
//       "</process-definition>" 
//     );
//     subProcessDefinition.addDefinition(new ContextDefinition());
    
//     // bind the sub-process to the super process definition
//     ProcessState processState = (ProcessState) superProcessDefinition.getNode("subprocessnode");
//     processState.setSubProcessDefinition(subProcessDefinition);
    
//     // create the super process definition
//     ProcessInstance superProcessInstance = new ProcessInstance(superProcessDefinition);
//     Token superToken = superProcessInstance.getRootToken();
    
//     // set some variableInstances in the super process
//     ContextInstance superContextInstance = superProcessInstance.getContextInstance();
//     superContextInstance.setVariable("a", "hello");
//     superContextInstance.setVariable("b", new Integer(3));
    
//     // start execution of the super process
//     superProcessInstance.signal();

//     // check if the variableInstances have been copied properly into the sub process
//     ProcessInstance subProcessInstance = superToken.getSubProcessInstance();
//     ContextInstance subContextInstance = subProcessInstance.getContextInstance();

//     assertEquals("hello", subContextInstance.getVariable("aa"));
//     assertEquals(new Integer(3), subContextInstance.getVariable("bb"));
//     // update variable aa
//     subContextInstance.setVariable("aa", "new hello");

//     // end the subprocess
//     subProcessInstance.signal();
    
//     // now check if the subprocess variableInstances have been copied into the super process
//     assertEquals("new hello", superContextInstance.getVariable("a"));
//     assertEquals(new Integer(3), superContextInstance.getVariable("b"));
//   }
}
