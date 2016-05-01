<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class TransitionDbTest extends AbstractDbTestCase {
	
	/**
	 * @test
	 */
	public function testTranisitionName() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <node name='n'>
          <transition name='t' to='n' />
        </node>
      </process-definition>");
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$n = $processDefinition->getNode("n");
		$t = $n->getLeavingTransitionsList()[0];
		$this->assertEquals("t", $t->getName());
	}
	
	/**
	 * @test
	 */
	public function testTranisitionFrom() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <node name='n'>
          <transition name='t' to='m' />
        </node>
        <node name='m' />
      </process-definition>");
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$n = $processDefinition->getNode("n");
		$t = $n->getLeavingTransitionsList()[0];
		$this->assertSame($n, $t->getFrom());
	}
	
	/**
	 * @test
	 */
	public function testTranisitionTo() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <node name='n'>
        	  <transition name='t' to='m' />
        </node>
        <node name='m' />
      </process-definition>");
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$n = $processDefinition->getNode("n");
		$m = $processDefinition->getNode("m");
		$t = $n->getLeavingTransitionsList()[0];
		$this->assertSame($m, $t->getTo());
	}
	
	/**
	 * @test
	 */
	public function testUnnamedTransition() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>
		 <node name='n'>
		  <transition to='m' />
		 </node>
		 <node name='m' />
		</process-definition>");
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$n = $processDefinition->getNode("n");
		$m = $processDefinition->getNode("m");
		
		$t = $n->getDefaultLeavingTransition();
		$this->assertNotNull($t);
		$this->assertEquals($n, $t->getFrom());
		$this->assertEquals($m, $t->getTo());
		$this->assertEquals(1, sizeof($n->getLeavingTransitionsList()));
	}
	
	/**
	 * @test
	 */
	public function testTwoUnnamedTransitions() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>
		 <node name='n'>
			 <transition to='m' />
			 <transition to='o' />
		 </node>
		 <node name='m' />
		 <node name='o' />
		</process-definition>");
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$n = $processDefinition->getNode("n");
		$m = $processDefinition->getNode("m");
		
		$t = $n->getDefaultLeavingTransition();
		$this->assertNotNull($t);
		$this->assertEquals($n, $t->getFrom());
		$this->assertEquals($m, $t->getTo());
		$this->assertEquals(2, sizeof($n->getLeavingTransitionsList()));
		
		$this->assertEquals(1, sizeof($n->getLeavingTransitionsMap()));
		$t = $n->getLeavingTransition(null);
		$this->assertNotNull($t);
		$this->assertEquals($n, $t->getFrom());
		$this->assertEquals($m, $t->getTo());
	}
	public function testThreeSameNameTransitions() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>
	 <node name='n'>
	   <transition name='t' to='m' />
	   <transition name='t' to='o' />
	   <transition name='t2' to='p' />
	 </node>
	 <node name='m' />
	 <node name='o' />
	 <node name='p' />
	</process-definition>");
		
		$processDefinition = $this->saveAndReload($processDefinition);
		$n = $processDefinition->getNode("n");
		$m = $processDefinition->getNode("m");
		$p = $processDefinition->getNode("p");
		
		$t = $n->getDefaultLeavingTransition();
		$this->assertNotNull($t);
		$this->assertEquals("t", $t->getName());
		$this->assertEquals($n, $t->getFrom());
		$this->assertEquals($m, $t->getTo());
		$this->assertEquals(3, sizeof($n->getLeavingTransitionsList()));
		
		$this->assertEquals(2, sizeof($n->getLeavingTransitionsMap()));
		$t = $n->getLeavingTransition("t");
		$this->assertNotNull($t);
		$this->assertEquals("t", $t->getName());
		$this->assertEquals($n, $t->getFrom());
		$this->assertEquals($m, $t->getTo());
		$t = $n->getLeavingTransition("t2");
		$this->assertNotNull($t);
		$this->assertEquals("t2", $t->getName());
		$this->assertEquals($n, $t->getFrom());
		$this->assertEquals($p, $t->getTo());
	}
}
