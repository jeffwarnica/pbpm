<?php
namespace com\coherentnetworksolutions\pbpm\taskmgmt\def;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class TaskDbTest extends AbstractDbTestCase {

	/**
	 * @test
	 */
	public function testTaskName() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>" .
			 "  <task name='wash car' />" .
			 "</process-definition>");

		$processDefinition = $this->saveAndReload($processDefinition);
		$taskMgmtDefinition = $processDefinition->getTaskMgmtDefinition();
		$task = $taskMgmtDefinition->getTask("wash car");
		$this->assertEquals("wash car", $task->getName());
	}

}
