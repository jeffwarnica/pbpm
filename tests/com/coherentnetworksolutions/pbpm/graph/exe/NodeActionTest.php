<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;
use com\coherentnetworksolutions\pbpm\graph\def\ActionHandler;

global $scenario;
class NodeActionTest extends AbstractDbTestCase {
	private $processDefinition;
	private $processInstance;
	private $token;
	private $n;
	private $a;
	private $b;
	private $c;
	public $scenario = 0;
	public function setUp() {
		parent::setUp();
		$this->processDefinition = ProcessDefinition::parseXmlString(<<<'EOXML'
	    <process-definition>
	      <start-state>
	        <transition to='n' />
	      </start-state>
	      <node name='n'>
	        <action class='com\coherentnetworksolutions\pbpm\graph\exe\RuntimeCalculation'/>
	        <transition name='a' to='a' />
	        <transition name='b' to='b' />
	        <transition name='c' to='c' />
	      </node>
	      <state name='a' />
	      <state name='b' />
	      <state name='c' />
	      <task name='undress' />
	    </process-definition>
EOXML
);
		$this->processInstance = new ProcessInstance($this->processDefinition);
		
		$this->token = $this->processInstance->getRootToken();
		$this->n = $this->processDefinition->getNode("n");
		$this->a = $this->processDefinition->getNode("a");
		$this->b = $this->processDefinition->getNode("b");
		$this->c = $this->processDefinition->getNode("c");
	}
	public function testSituation1() {
		$this->scenario = 1;
		global $scenario;
		$scenario = 1;
		$this->processInstance->signal();
		$this->assertSame($this->a, $this->token->getNode());
	}
	public function testSituation2() {
		$this->scenario = 2;
		global $scenario;
		$scenario = 2;
		$this->processInstance->signal();
		$this->assertSame($this->b, $this->token->getNode());
	}
	public function testSituation3() {
		$this->scenario = 3;
		global $scenario;
		$scenario = 3;
		$this->processInstance->signal();
		$this->assertSame($this->c, $this->token->getNode());
	}
	public function testSituation4() {
		$this->scenario = 4;
		global $scenario;
		$scenario = 4;
		$this->processInstance->signal();
		$this->assertSame($this->n, $this->token->getNode());
	}
}
class RuntimeCalculation implements ActionHandler {
	public function execute(ExecutionContext $executionContext) {
		global $scenario;
		
		if ($scenario == 1) {
			$executionContext->leaveNode("a");
		} else if ($scenario == 2) {
			$executionContext->leaveNode("b");
		} else if ($scenario == 3) {
			$executionContext->leaveNode("c");
		} else if ($scenario == 4) {
			// do nothing and behave like a state
		}
	}
}