<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

class SuperStateTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * @test
	 */
	public function testChildNodeAdditions() {
		$superState = new SuperState();
		$superState->addNode(new Node("one"));
		$superState->addNode(new Node("two"));
		$superState->addNode(new Node("three"));
		
		$this->assertEquals(3, sizeof($superState->getNodes()));
		$this->assertEquals($superState->getNode("one"), $superState->getNodes()[0]);
		$this->assertEquals($superState->getNode("two"), $superState->getNodes()[1]);
		$this->assertEquals($superState->getNode("three"), $superState->getNodes()[2]);
	}
	
	/**
	 * @test
	 */
	public function testChildNodeRemoval() {
		$superState = new SuperState();
		$superState->addNode(new Node("one"));
		$superState->addNode(new Node("two"));
		$superState->addNode(new Node("three"));
		$superState->removeNode($superState->getNode("two"));
		
		$this->assertEquals(2, sizeof($superState->getNodes()));
		$this->assertEquals($superState->getNode("one"), $superState->getNodes()[0]);
		// Java would have bubble up the array offsets, it was looking for ->get(1)... *sigh*
		$this->assertEquals($superState->getNode("three"), $superState->getNodes()[2]);
	}
	
	/**
	 * @test
	 */
	public function testSuperStateXmlParsing() {
		$processDefinition = ProcessDefinition::parseXmlString("
      <process-definition>
        <super-state name='phase one'>
          <node name='ignition' />
          <node name='explosion' />
          <node name='cleanup' />
          <node name='repare' />
        </super-state>
      </process-definition>");
		
		$this->assertEquals(1, sizeof($processDefinition->getNodes()));
		
		/**
		 *
		 * @var SuperState $phaseOne
		 */
		$phaseOne = $processDefinition->getNode("phase one");
		$this->assertNotNull($phaseOne);
		
		$this->assertEquals(4, sizeof($phaseOne->getNodes()));
		
		// Argh. PHP/ArrayCollection just doesn't renumber things. Sparse arrays, I've found a problem with you.
		// $this->assertSame($phaseOne->getNode("ignition"), $phaseOne->getNodes()[0]);
		// $this->assertSame($phaseOne->getNode("explosion"), $phaseOne->getNodes()[1]);
		// $this->assertSame($phaseOne->getNode("cleanup"), $phaseOne->getNodes()[2]);
		// $this->assertSame($phaseOne->getNode("repare"), $phaseOne->getNodes()[3]);
		
		// check parents
		$this->assertSame($processDefinition, $phaseOne->getParent());
		$this->assertSame($phaseOne, $phaseOne->getNode("ignition")->getParent());
		$this->assertSame($phaseOne, $phaseOne->getNode("explosion")->getParent());
		$this->assertSame($phaseOne, $phaseOne->getNode("cleanup")->getParent());
		$this->assertSame($phaseOne, $phaseOne->getNode("repare")->getParent());
		
		// check process definition references
		$this->assertSame($processDefinition, $phaseOne->getParent());
		$this->assertSame($processDefinition, $phaseOne->getNode("ignition")->getProcessDefinition());
		$this->assertSame($processDefinition, $phaseOne->getNode("explosion")->getProcessDefinition());
		$this->assertSame($processDefinition, $phaseOne->getNode("cleanup")->getProcessDefinition());
		$this->assertSame($processDefinition, $phaseOne->getNode("repare")->getProcessDefinition());
	}
	public function testNestedSuperStateXmlParsing() {
		$processDefinition = ProcessDefinition::parseXmlString("
	<process-definition>
	 <super-state name='phase one'>
	 <node name='ignition' />
	 <node name='explosion' />
	 <super-state name='cleanup'>
	 <node name='take brush' />
	 <node name='sweep floor' />
	 </super-state>
	 <node name='repare' />
	 </super-state>
	</process-definition>");
		
		/**
		 *
		 * @var SuperState $phaseOne
		 */
		$phaseOne = $processDefinition->getNode("phase one");
		$this->assertNotNull($phaseOne);
		// check phase one parent
		$this->assertSame($processDefinition, $phaseOne->getParent());
		
		// check phase one child nodes
		$phaseOneNodes = $phaseOne->getNodesMap();
		$this->assertNotEmpty($phaseOneNodes);
		$this->assertEquals(4, sizeof($phaseOneNodes));
		
		// $this->assertEquals("ignition", $phaseOneNodes[0]->getName());
		// $this->assertEquals("explosion", $phaseOneNodes[1]->getName());
		// $this->assertEquals("cleanup", $phaseOneNodes[2]->getName());
		// $this->assertEquals("repare", $phaseOneNodes[3]->getName());\
		
		// check phase one child nodes parent
		
		$this->assertEquals($phaseOne, $phaseOneNodes["ignition"]->getParent());
		$this->assertEquals($phaseOne, $phaseOneNodes["explosion"]->getParent());
		$this->assertEquals($phaseOne, $phaseOneNodes["cleanup"]->getParent());
		$this->assertEquals($phaseOne, $phaseOneNodes["repare"]->getParent());
		
		$cleanUp = $processDefinition->findNode("phase one/cleanup");
		
		$this->assertSame($cleanUp, $phaseOneNodes["cleanup"]);
		// check clea up child nodes
		$cleanUpNodes = $cleanUp->getNodesMap();
		$this->assertNotEmpty($cleanUpNodes);
		$this->assertEquals(2, sizeof($cleanUpNodes));
		$this->assertEquals("take brush", $cleanUpNodes["take brush"]->getName());
		$this->assertEquals("sweep floor", $cleanUpNodes["sweep floor"]->getName());
		// check clean up child nodes parent
		$this->assertEquals($cleanUp, $cleanUpNodes["take brush"]->getParent());
		$this->assertEquals($cleanUp, $cleanUpNodes["sweep floor"]->getParent());
		
		$this->assertEquals("take brush", $processDefinition->findNode("phase one/cleanup/take brush")->getName());
	}
	public function testNestedSuperStateXmlTransitionParsing() {
		$processDefinition = ProcessDefinition::parseXmlString("
	<process-definition name='NSSXTP_PD'>
	 <node name='preparation'>
		 <transition name='local' to='phase one' />
		 <transition name='superstate-node' to='phase one/cleanup' />
		 <transition name='nested-superstate-node' to='phase one/cleanup/take brush' />
	 </node>
	 <super-state name='phase one'>
		 <node name='ignition'>
			 <transition name='parentXXX' to='../preparation' />
			 <transition name='local' to='explosion' />
			 <transition name='superstate-node' to='cleanup/take brush' />
		 </node>
		 <node name='explosion' />
		 <super-state name='cleanup'>
			 <node name='take brush'>
				 <transition name='recursive-parent' to='../../preparation' />
				 <transition name='parent' to='../explosion' />
				 <transition name='local' to='take brush' />
				 <transition name='absolute-superstate' to='/phase one' />
				 <transition name='absolute-node' to='/phase two' />
			 </node>
		 	<node name='sweep floor' />
		 </super-state>
		 <node name='repare' />
	</super-state>
	<node name='phase two' />
	</process-definition>");
		
		$preparation = $processDefinition->getNode("preparation");
		$this->assertNotNull($preparation);
		$this->assertEquals("phase one", $preparation->getLeavingTransition("local")->getTo()->getName());
		$this->assertEquals("cleanup", $preparation->getLeavingTransition("superstate-node")->getTo()->getName());
		$this->assertEquals("take brush", $preparation->getLeavingTransition("nested-superstate-node")->getTo()->getName());
		
		$ignition = $processDefinition->findNode("phase one/ignition");
		$this->assertNotNull($ignition);
		$this->assertEquals("preparation", $ignition->getLeavingTransition("parentXXX")->getTo()->getName());
		$this->assertEquals("explosion", $ignition->getLeavingTransition("local")->getTo()->getName());
		$this->assertEquals("take brush", $ignition->getLeavingTransition("superstate-node")->getTo()->getName());
		
		$cleanup = $processDefinition->findNode("phase one/cleanup/take brush");
		$this->assertNotNull($cleanup);
		$this->assertEquals("preparation", $cleanup->getLeavingTransition("recursive-parent")->getTo()->getName());
		$this->assertEquals("explosion", $cleanup->getLeavingTransition("parent")->getTo()->getName());
		$this->assertEquals("take brush", $cleanup->getLeavingTransition("local")->getTo()->getName());
		$this->assertEquals("phase one", $cleanup->getLeavingTransition("absolute-superstate")->getTo()->getName());
		$this->assertEquals("phase two", $cleanup->getLeavingTransition("absolute-node")->getTo()->getName());
	}
	
	/**
	 * @test
	 */
	public function testSuperStateTransitionsParsing() {
		$processDefinition = ProcessDefinition::parseXmlString("
	<process-definition>
		<node name='preparation'>
  	 		<transition to='phase one' />
	 	</node>
	 	<super-state name='phase one'>
	 		<transition name='to-node' to='preparation' />
	 		<transition name='self' to='phase one' />
		</super-state>
	</process-definition>");
		
		$this->assertEquals("preparation", $processDefinition->getNode("phase one")->getLeavingTransition("to-node")->getTo()->getName());
		$this->assertEquals("phase one", $processDefinition->getNode("phase one")->getLeavingTransition("self")->getTo()->getName());
		$this->assertEquals("phase one", $processDefinition->getNode("preparation")->getDefaultLeavingTransition()->getTo()->getName());
	}
	
// 	/**
// 	 * @test
// 	 */
// 	public function testLeavingTransitionOfSuperState() {
// 		$processDefinition = ProcessDefinition::parseXmlString("
// 	<process-definition>
// 		<super-state name='super'>
// 			<node name='child' />
// 	 		<transition name='take me' to='super' />
// 	 	</super-state>
// 	</process-definition>
// 	");
		
// 		$child = $processDefinition->findNode("super/child");
		
// 		var_dump($child);		
		
// 		$takeMe = $processDefinition->getNode("super")->getLeavingTransition("take me");
// 		$takeMeFromChild = $child->getLeavingTransition("take me");
		
// 		$this->assertNotNull($child, "child should not be null");
// 		$this->assertNotNull($takeMe, "takeMe should not be null");
// 		$this->assertNotNull($takeMeFromChild, "takeMeFromChild should not be null");
		
// 		$this->assertSame($takeMe, $child->getLeavingTransition("take me"));
// 	}
	
}
