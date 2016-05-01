<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

class LeavingTransitionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Node
     */
    private $node;

    /**
     * @var Transition
     */
    private $transition;

    /**
     * @before
     */
    public function setupCrap() {
        $this->node = new Node("n");
        $this->transition = new Transition("t");
    }

    /**
     * @test
     */
    public function testAddLeavingTransitionWithoutName() {
        $transitionWithoutName = new Transition();
        $this->node->addLeavingTransition($transitionWithoutName);
        $this->assertEquals(1, sizeof($this->node->getLeavingTransitionsMap()));
        $this->assertSame($transitionWithoutName, $this->node->getLeavingTransition(null));
        $fuckphp7 = $this->node->getLeavingTransitionsMap();
        $this->assertSame($transitionWithoutName, array_pop($fuckphp7));
        $this->assertSame($this->node, $transitionWithoutName->getFrom());
    }

    /**
     * @test
     */
    public function testAddLeavingTransition() {
        $this->node->addLeavingTransition($this->transition);
        $this->assertEquals(1, sizeof($this->node->getLeavingTransitionsMap()));
        $this->assertSame($this->transition, $this->node->getLeavingTransition("t"));
        $fuckphp7 = $this->node->getLeavingTransitionsMap();
        $this->assertSame($this->transition, array_pop($fuckphp7));
        $this->assertSame($this->node, $this->transition->getFrom());
    }

    /**
     * @test
     */
    public function testRename() {
        $this->node->addLeavingTransition($this->transition);
        $this->transition->setName("t2");
        $this->assertSame($this->transition, $this->node->getLeavingTransition("t2"));
    }

    /**
     * @test
     */
    public function testRemoveLeavingTransition() {
        $this->node->addLeavingTransition($this->transition);
        $this->node->removeLeavingTransition($this->transition);
        $this->assertNull($this->node->getLeavingTransition("t"));
        $this->assertNull($this->transition->getFrom());
        $this->assertEquals(0, sizeof($this->node->getLeavingTransitionsMap()));
    }

    /**
     * @test
     */
    public function testOverwriteLeavingTransitionAllowed() {
        $this->node->addLeavingTransition($this->transition);
        $this->node->addLeavingTransition(new Transition());
        $this->assertEquals(2, sizeof($this->node->getLeavingTransitionsMap()));
        
        foreach ( $this->node->getLeavingTransitionsMap() as $transition ) {
            $this->assertSame($this->node, $transition->getFrom());
        }
    }

    /**
     * @test
     */
    public function testLeavingTransitionsXmlTest() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <state name='a'>
                            <transition to='b' />
                            <transition name='to-c' to='c' />
                            <transition name='to-d' to='d' />
                          </state>
                          <state name='b' />
                          <state name='c'/>
                          <state name='d'/>
                        </process-definition>");
        
        $leavingTransitions = $processDefinition->getNode("a")->getLeavingTransitionsMap();
        $this->assertEquals(3, sizeof($leavingTransitions));
        $first = array_shift($leavingTransitions);
        $this->assertSame("", $first->getName());
    }
}