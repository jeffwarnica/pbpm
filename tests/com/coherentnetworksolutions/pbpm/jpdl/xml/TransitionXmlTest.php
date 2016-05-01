<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;

require_once("AbstractXMLTestCase.php");

class TransitionXmlTest extends AbstractXmlTestCase {
    /**
     * @test
     */
    public function testReadNodeTransition() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='a'>
                            <transition to='b' name='_to_b_'/>
                          </node>
                          <node name='b' />
                        </process-definition>"
        );
        $a = $processDefinition->getNode("a");
        $b = $processDefinition->getNode("b");
        
        $this->assertSame($a, $a->getDefaultLeavingTransition()->getFrom());
        $this->assertSame($b, $a->getDefaultLeavingTransition()->getTo());
    }
    
    /**
     * @test
     */
    public function testWriteNodeTransition() {
        $processDefinition = new ProcessDefinition();
        $a = new Node("a");
        $b = new Node("b");
        $processDefinition->addNode($a);
        $processDefinition->addNode($b);
    
        $t = new Transition();
        $a->addLeavingTransition($t);
        $b->addArrivingTransition($t);
    
        $element = $this->toXmlAndParse( $processDefinition, "/process-definition/node[1]/transition" );
        $this->assertNotNull($element);
        $this->assertEquals("transition", $element->nodeName);
        $this->assertEquals(1, $element->attributes->length);
        $this->assertEquals("b", $element->getAttribute("to"));
    }
    
    /**
     * @test
     */
    public function testReadNodeTransitionName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='a'>
                            <transition name='hertransition' to='b' />
                          </node>
                          <node name='b' />
                        </process-definition>"
        );
    
        $element = $this->toXmlAndParse($processDefinition, "/process-definition/node[1]/transition[1]" );
        $this->assertNotNull($element);
        $this->assertEquals("hertransition", $element->getAttribute("name"));
    }
    
    public function testWriteNodeTransitionName() {
        $processDefinition = new ProcessDefinition();
        $a = new Node("a");
        $b = new Node("b");
        $processDefinition->addNode($a);
        $processDefinition->addNode($b);
    
        $t = new Transition("hertransition");
        $a->addLeavingTransition($t);
        $b->addArrivingTransition($t);
    
        $element = $this->toXmlAndParse($processDefinition, "/process-definition/node[1]/transition" );
        $this->assertNotNull($element);
        $this->assertEquals("hertransition", $element->getAttribute("name"));
    }
    
    public function testTransitionOrder() {
        $processDefinition = new ProcessDefinition();
        $a = new Node("a");
        $b = new Node("b");
        $processDefinition->addNode($a);
        $processDefinition->addNode($b);
    
        $t = new Transition("one");
        $a->addLeavingTransition($t);
        $b->addArrivingTransition($t);
    
        $t = new Transition("two");
        $a->addLeavingTransition($t);
        $b->addArrivingTransition($t);
    
        $t = new Transition("three");
        $a->addLeavingTransition($t);
        $b->addArrivingTransition($t);
    
        $element = $this->toXmlAndParse($processDefinition);
        $this->assertNotNull($element);
        
        $xp = new \DOMXPath($element->ownerDocument);
        
        $this->assertEquals( "one", $xp->evaluate("/process-definition/node[1]/transition[1]")->item(0)->getAttribute("name"));
        $this->assertEquals( "two", $xp->evaluate("/process-definition/node[1]/transition[2]")->item(0)->getAttribute("name"));
        $this->assertEquals( "three", $xp->evaluate("/process-definition/node[1]/transition[3]")->item(0)->getAttribute("name"));
        
    }
}