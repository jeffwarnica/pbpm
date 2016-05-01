<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class BusinessKeyDbTest extends AbstractDbTestCase {

	public function testSimpleBusinessKey() {
    $processDefinition = new ProcessDefinition("businesskeytest");
    $this->deployProcessDefinition($processDefinition);

    $processInstance = $this->pbpmContext->newProcessInstanceForUpdate("businesskeytest");
    $processInstance->setKey("businesskey1");

    $this->newTransaction();
    $processInstance = $this->pbpmContext->newProcessInstanceForUpdate("businesskeytest");
    processInstance.setKey("businesskey2");

//     newTransaction();
//     $processDefinition = jbpmContext.getGraphSession()
//       .findLatestProcessDefinition("businesskeytest");
//     processInstance = jbpmContext.getProcessInstance(processDefinition, "businesskey1");
//     assertEquals("businesskey1", processInstance.getKey());
//   }

//   public function  testDuplicateBusinessKeyInDifferentProcesses() {
//     ProcessDefinition processDefinitionOne = new ProcessDefinition("businesskeytest1");
//     deployProcessDefinition(processDefinitionOne);

//     ProcessDefinition processDefinitionTwo = new ProcessDefinition("businesskeytest2");
//     deployProcessDefinition(processDefinitionTwo);

//     jbpmContext.newProcessInstanceForUpdate("businesskeytest1").setKey("duplicatekey");
//     jbpmContext.newProcessInstanceForUpdate("businesskeytest2").setKey("duplicatekey");
  }
}
