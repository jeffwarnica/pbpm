<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;

require_once("AbstractXMLTestCase.php");

class NodeXmlTest extends AbstractXmlTestCase {

    /**
     * @test
     */
    public function testReadNode() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                           <node />
                        </process-definition>"
        );
        $this->assertNotNull($processDefinition->getNodes()->first());
    }

    /**
     * @test
     */
    public function testWriteNode() {
        $processDefinition = new ProcessDefinition();
        $processDefinition->addNode(new Node());
        $element = $this->toXmlAndParse($processDefinition, "/process-definition/node" );
        $element = $this->toXmlAndParse($processDefinition);
        $this->assertNotNull($element);
        $this->assertEquals(0, $element->attributes->length);
        
    }

    /**
     * @test
     */
    public function testReadNodeName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='wash car' />
                        </process-definition>"
        );
        $this->assertEquals("wash car", $processDefinition->getNode("wash car")->getName());
    }

    /**
     * @test
     */
    public function testWriteNodeName() {
        $processDefinition = new ProcessDefinition();
        $processDefinition->addNode(new Node("n"));
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/node" );
        $this->assertNotNull($element);
        $this->assertEquals("n", $element->getAttribute("name"));
        $this->assertEquals(1, $element->attributes->length);
    }

    /**
     * @test
     */
    public function testReadNodeEvents() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='n'>
                            <event type='node-enter'/>
                            <event type='customeventtype' />
                          </node>
                        </process-definition>"
        );
        $node = $processDefinition->getNode("n");
        $this->assertEquals(2,$node->getEvents()->count());
        $this->assertEquals("node-enter",$node->getEvent("node-enter")->getEventType());
        $this->assertEquals("customeventtype",$node->getEvent("customeventtype")->getEventType());
    }

    /**
     * @test
     */
    public function testWriteNodeEvents() {
        $processDefinition = new ProcessDefinition();
        $node = new Node("n");
        $processDefinition->addNode($node);
        $node->addEvent(new Event("one"));
        $node->addEvent(new Event("two"));
        $node->addEvent(new Event("three"));

//         $this->printXml($processDefinition);
        
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/node" );
        $this->assertNotNull($element);
        $eventElemList = $element->getElementsByTagName("event");
        $this->assertEquals(3, $eventElemList->length);
        $this->assertEquals("one", $eventElemList->item(0)->getAttribute("type"));
        $this->assertEquals("two", $eventElemList->item(1)->getAttribute("type"));
        $this->assertEquals("three", $eventElemList->item(2)->getAttribute("type"));
    }

    /**
     * @test
     */
    public function testReadNodeTransitions() {
        $processDefinition = ProcessDefinition::parseXmlString("
                        <process-definition>
                          <node name='n'>
                            <transition name='one' to='n'/>
                            <transition name='two' to='n'/>
                            <transition name='three' to='n'/>
                          </node>
                        </process-definition>"
        );
        $node = $processDefinition->getNode("n");
        $leavingTransitions = $node->getLeavingTransitions();
        $this->assertEquals(3, $leavingTransitions->count());
        $this->assertEquals("one", $leavingTransitions->offsetGet(0)->getName());
        $this->assertEquals("two", $leavingTransitions->offsetGet(1)->getName());
        $this->assertEquals("three", $leavingTransitions->offsetGet(2)->getName());
    }

    public function testWriteNodeTransitions() {
        $processDefinition = new ProcessDefinition();
        $node = new Node("n");
        $processDefinition->addNode($node);
        $node->addLeavingTransition(new Transition("one"));
        $node->addLeavingTransition(new Transition("two"));
        $node->addLeavingTransition(new Transition("three"));
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/node" );
        $leavingTransitionsElemList = $element->getElementsByTagName("transition");
        
        $this->assertEquals(3, $leavingTransitionsElemList->length);
        $this->assertEquals("one", $leavingTransitionsElemList->item(0)->getAttribute("name"));
        $this->assertEquals("two", $leavingTransitionsElemList->item(1)->getAttribute("name"));
        $this->assertEquals("three", $leavingTransitionsElemList->item(2)->getAttribute("name"));
    }
}