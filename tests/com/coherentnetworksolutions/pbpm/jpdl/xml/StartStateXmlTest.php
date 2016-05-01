<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\node\StartState;

require_once("AbstractXMLTestCase.php");

class StartStateXmlTest extends AbstractXmlTestCase {

    /**
     * @test
     */
    public function testParseStartState() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>" .
                        "  <start-state />" .
                        "</process-definition>"
        );
        $this->assertNotNull($processDefinition->getStartState());
    }

    /**
     * @test
     */
    public function testParseStartStateName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>" . 
                        "  <start-state name='start'/>" .
                        "</process-definition>"
        );
        $this->assertEquals('com\coherentnetworksolutions\pbpm\graph\node\StartState', get_class($processDefinition->getStartState()));
    }

    public function testWriteStartState() {
        $processDefinition = new ProcessDefinition();
        $processDefinition->setStartState( new StartState() );
        
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/start-state[1]" );
        $this->assertNotNull($element);
        $this->assertEquals("start-state", $element->nodeName);
        $this->assertEquals(0, $element->attributes->length);
    }

    public function testWriteStartStateName() {
        $processDefinition = new ProcessDefinition();
        $processDefinition->setStartState( new StartState("mystartstate") );
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/start-state[1]" );
        $this->assertEquals("start-state", $element->nodeName);
        $this->assertEquals(1, $element->attributes->length);
        $this->assertEquals("mystartstate", $element->getAttribute("name"));
    }


}
