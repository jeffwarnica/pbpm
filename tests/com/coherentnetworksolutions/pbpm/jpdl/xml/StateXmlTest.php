<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\node\State;

require_once("AbstractXMLTestCase.php");

class StateXmlTest extends AbstractXmlTestCase {

    /**
     * @test
     */
    public function testMultipleStates() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>" .
                        "  <state name='one' />" .
                        "  <state name='two' />" .
                        "  <state name='three' />" .
                        "  <state name='four' />" .
                        "</process-definition>"
        );
        $this->assertEquals(4, $processDefinition->getNodes()->count());
        $this->assertEquals('com\coherentnetworksolutions\pbpm\graph\node\State', get_class($processDefinition->getNode("one")));
    }
    
    /**
     * @test
     */
    public function testState(){
        $processDefinition = new ProcessDefinition();
        $processDefinition->addNode( new State() );
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/state[1]" );
        $this->assertNotNull($element);
        $this->assertEquals("state", $element->nodeName);
        $this->assertEquals(0, $element->attributes->length);
    }
    
    /**
     * @test
     */
    public function testStateName() {
        $processDefinition = new ProcessDefinition();
        $processDefinition->addNode( new State("mystate") );
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/state[1]" );
        $this->assertNotNull($element);
        $this->assertEquals("state", $element->nodeName);
        $this->assertEquals(1, $element->attributes->length);
        $this->assertEquals("mystate", $element->getAttribute("name"));
    }
    
    /**
     * @test
     */
    public function testThreeStatesOrder()  {
        $processDefinition = new ProcessDefinition();
        $processDefinition->addNode( new State("one") );
        $processDefinition->addNode( new State("two") );
        $processDefinition->addNode( new State("three") );
        $processDefinition->addNode( new State("four") );
        $element = $this->toXmlAndParse( $processDefinition );
        $this->assertNotNull($element);
        
        $xp = new \DOMXPath($element->ownerDocument);
        
        $this->assertEquals( "one", $xp->evaluate("/process-definition/state[1]")->item(0)->getAttribute("name"));
        $this->assertEquals( "two", $xp->evaluate("/process-definition/state[2]")->item(0)->getAttribute("name"));
        $this->assertEquals( "three", $xp->evaluate("/process-definition/state[3]")->item(0)->getAttribute("name"));
        $this->assertEquals( "four", $xp->evaluate("/process-definition/state[4]")->item(0)->getAttribute("name"));
    }
}