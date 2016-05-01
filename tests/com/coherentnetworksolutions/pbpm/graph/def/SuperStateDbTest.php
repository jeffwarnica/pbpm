<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class SuperStateDbTest extends AbstractDbTestCase {

	/**
	 * @test
	 */
  public function testGetNodesWithSuperState() {
    $processDefinition = ProcessDefinition::parseXmlString("<process-definition>
        <node name='phase zero'/>
        <super-state name='phase one'>
          <node name='ignition' />
          <node name='explosion' />
          <super-state name='cleanup'>
            <node name='take brush' />
            <node name='sweep floor' />
            <node name='blow dry' />
          </super-state>
          <node name='repare' />
        </super-state>
      </process-definition>");

    $processDefinition = $this->saveAndReload($processDefinition);
    $expectedNodeNames = array();
    $expectedNodeNames[] = "phase zero";
    $expectedNodeNames[] = "phase one";
    $this->assertEquals($expectedNodeNames, $this->getNodeNames($processDefinition->getNodes()));

    $phaseOne = $processDefinition->getNode("phase one");
    $expectedNodeNames = array();
    $expectedNodeNames[] = "ignition";
    $expectedNodeNames[] = "explosion";
    $expectedNodeNames[] = "cleanup";
    $expectedNodeNames[] = "repare";
    $this->assertEquals($expectedNodeNames, $this->getNodeNames($phaseOne->getNodes()));

    $cleanup = $phaseOne->getNode("cleanup");
    $expectedNodeNames = array();
    $expectedNodeNames[] = "take brush";
    $expectedNodeNames[] = "sweep floor";
    $expectedNodeNames[] = "blow dry";
    $this->assertEquals($expectedNodeNames, $this->getNodeNames($cleanup->getNodes()));
  }

  private function getNodeNames($nodes) {
    $nodeNames = array();

    $iter = $nodes->getIterator();
    while($iter->valid()) {
    	$node = $iter->current();
    	$nodeNames[] = $node->getName();
    	$iter->next();
    }
    return $nodeNames;
  }
}
