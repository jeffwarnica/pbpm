<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

use Doctrine\Common\Collections\ArrayCollection;
class ArrivingTransitionsTest extends \PHPUnit_Framework_TestCase {

     /**
      * @var Node
      */
    private $n;
    /**
     * 
     * @var Transition
     */
    private $t;
    
    /**
     * @before
     */
    public function basicThingies() {
     $this->t= new Transition("t");
     $this->n= new Node("n");
    }
    
    /**
     * @test
     */
    public function testAddArrivingTransition() {
        $this->n->addArrivingTransition( $this->t );
        $this->assertSame( $this->n, $this->t->getTo() );
        $this->assertEquals( 1, sizeof($this->n->getArrivingTransitions()) );
//         $ats = $this->n->getArrivingTransitions()->first();
//         var_dump($ats);
        $this->assertEquals( $this->t, $this->n->getArrivingTransitions()->first());
    }

    public function testRemoveArrivingTransition() {
        $this->n->addArrivingTransition( $this->t );
        $this->n->removeArrivingTransition( $this->t );
        $this->assertNull( $this->t->getTo() );
        $this->assertEquals( 0, sizeof($this->n->getArrivingTransitions()) );
    }

    public function testArrivingTransitions() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <state name='a'>
                            <transition name='to-c' to='c' />
                          </state>
                          <state name='b'>
                            <transition name='to-c' to='c' />
                          </state>
                          <state name='c'/>
                        </process-definition>"
        );

        $arrivingTransitions = $processDefinition->getNode("c")->getArrivingTransitions();
        $this->assertEquals(2, sizeof($arrivingTransitions));

        $fromNodes = new ArrayCollection();
        foreach ($arrivingTransitions as $transition) {
            $fromNodes->add( $transition->getFrom() );
            $this->assertEquals("to-c", $transition->getName());
        }

        $expectedFromNodes = new ArrayCollection();
        $expectedFromNodes->add($processDefinition->getNode("a"));
        $expectedFromNodes->add($processDefinition->getNode("b"));

        $this->assertEquals($expectedFromNodes, $fromNodes);
    }
}