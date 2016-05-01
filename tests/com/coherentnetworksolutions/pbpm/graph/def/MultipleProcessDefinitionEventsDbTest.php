<?php 
namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;
class MultipleProcessDefinitionEventsDbTest extends AbstractDbTestCase {

	/**
	 * @test
	 */
  public function testEventPersistence() {
    // Add a start state so that state '1' gets assigned id = 2
    $processDefinitionOne = ProcessDefinition::parseXmlString("<process-definition name='one'>" .
      "  <start-state name='start'>" .
      "    <transition name='start transition to 1' to='1' />" .
      "  </start-state>" .
      "  <state name='1'>" .
      "    <event type='node-enter'>" .
      "      <action class='foo' />" .
      "    </event>" .
      "  </state>" .
      "</process-definition>");
    $this->deployProcessDefinition($processDefinitionOne);

    $processDefinitionTwo = ProcessDefinition::parseXmlString("<process-definition name='two'>" .
      "  <state name='1'>" .
      "    <event type='node-enter'>" .
      "      <action class='bar' />" .
      "    </event>" . 
      "  </state>" .
      "</process-definition>");
    $this->deployProcessDefinition($processDefinitionTwo);

    $processDefinitionOne = $this->graphSession->loadProcessDefinition($processDefinitionOne->getId());
    $processDefinitionTwo = $this->graphSession->loadProcessDefinition($processDefinitionTwo->getId());

    $stateOne = $processDefinitionOne->getNode("1");
    $stateTwo = $processDefinitionTwo->getNode("1");
    $this->assertNotSame($stateOne->getEvent("node-enter"), $stateTwo->getEvent("node-enter"));
	
    $processEvents = $processDefinitionTwo->getEvents();
    $this->assertCount(0, $processEvents, "Process Definition should not have any events (but has: [" . sizeof($processEvents) . "]");
  }
}
