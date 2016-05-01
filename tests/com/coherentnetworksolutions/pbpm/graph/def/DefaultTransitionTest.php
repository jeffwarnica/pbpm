<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

class DefaultTransitionsTest extends \PHPUnit_Framework_TestCase {
    
    private $n;
    private $n2;
    private $n3;
    private $t;
    private $t1;
    private $t2;
    private $t3;
    
    /**
     * @before
     */
    public function setupCrap() {
        $this->n = new Node();
        $this->n2 = new Node();
        $this->n3 = new Node();
        $this->t = new Transition();
        $this->t1 = new Transition("one");
        $this->t2 = new Transition("two");
        $this->t3 = new Transition("three");
    }

    /**
     * @test
     */
    public function testOneTransition() {
        $this->n->addLeavingTransition($this->t);
        $this->assertSame($this->t, $this->n->getDefaultLeavingTransition());
    }
    
    /**
     * @test
     */
    public function testUnnamedAndNamedTransition() {
        $this->n->addLeavingTransition($this->t);
        $this->n->addLeavingTransition($this->t1);
        $this->assertSame($this->t, $this->n->getDefaultLeavingTransition());
    }
    
    /**
     * @test
     */
    public function testNamedAndUnnamedTransition() {
        $this->n->addLeavingTransition($this->t1);
        $this->n->addLeavingTransition($this->t);
        $this->assertSame($this->t1, $this->n->getDefaultLeavingTransition());
    }
    /**
     * @test
     */    
    public function test3NamedTransitions() {
        $this->n->addLeavingTransition($this->t1);
        $this->n->addLeavingTransition($this->t2);
        $this->n->addLeavingTransition($this->t3);
        $this->assertSame($this->t1, $this->n->getDefaultLeavingTransition());
    }
    /**
     * @test
     */    
    public function testAddRemoveAddScenario() {
        $this->n->addLeavingTransition($this->t1);
        $this->n->addLeavingTransition($this->t2);
        $this->n->addLeavingTransition($this->t3);
        $this->assertSame($this->t1, $this->n->getDefaultLeavingTransition());
        $this->n->removeLeavingTransition($this->t1);
        $this->assertSame($this->t2, $this->n->getDefaultLeavingTransition());
        $this->n->removeLeavingTransition($this->t2);
        $this->assertSame($this->t3, $this->n->getDefaultLeavingTransition());
        $this->n->removeLeavingTransition($this->t3);
        $this->assertNull($this->n->getDefaultLeavingTransition());
        $this->n->addLeavingTransition($this->t2);
        $this->assertSame($this->t2, $this->n->getDefaultLeavingTransition());
    }
    
    /**
     * @test
     */
    public function testDestinationOfDefaultTransition() {
        $this->n->addLeavingTransition($this->t);
        $this->n->removeLeavingTransition($this->t);
        $this->n2->addLeavingTransition($this->t);
        $this->n3->addLeavingTransition($this->t2);
        $this->n3->removeLeavingTransition($this->t2);
        $this->n2->addLeavingTransition($this->t2);
    
        $this->assertEquals($this->n2, $this->n2->getDefaultLeavingTransition()->getFrom() );
    }
}