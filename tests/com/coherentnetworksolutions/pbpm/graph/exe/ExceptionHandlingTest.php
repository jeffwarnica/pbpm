<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\ActionHandler;
use com\coherentnetworksolutions\pbpm\graph\def\DelegationException;

class ExceptionHandlingTest extends \PHPUnit_Framework_TestCase {
	static $executedActions = [ ];
	
	/**
	 * @before
	 *
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		ExceptionHandlingTest::$executedActions = [ ];
	}
	
	/**
	 * @expectedException com\coherentnetworksolutions\pbpm\graph\def\DelegationException
	 * //close enough
	 * @expectedExceptionMessageRegExp /BatterException/
	 */
	public function testUncaughtException() {
		$processDefinition = ProcessDefinition::parseXmlString("
    		<process-definition>
    			<start-state>
    				<transition to='play ball' />
        		</start-state>
    			<state name='play ball'>
    				<event type='node-enter'>
        				<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
    				</event>
    			</state>
    		</process-definition>");
		
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
	}
	
	/**
	 * @expectedException \RuntimeException
	 */
	public function testUncaughtRuntimeException() {
		$processDefinition = ProcessDefinition::parseXmlString("
		<process-definition>
			<start-state>
				<transition to='play ball' />
			</start-state>
			<state name='play ball'>
				<event type='node-enter'>
					<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\RuntimeBatter' />
				</event>
			</state>
		</process-definition>");
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
	}
	public function testSimpleCatchAll() {
		$processDefinition = ProcessDefinition::parseXmlString("
			<process-definition>
				<start-state>
					<transition to='play ball' />
				</start-state>
				<state name='play ball'>
					<event type='node-enter'>
						<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
					</event>
					<exception-handler>
						<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher'/>
					</exception-handler>
				</state>
			</process-definition>");
		
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
		$this->assertEquals(1, sizeof(ExceptionHandlingTest::$executedActions));
		$executedPitcher = ExceptionHandlingTest::$executedActions[0];
		$this->assertSame("com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher", get_class($executedPitcher));
	}
	public function testCatchOnlyTheSpecifiedException() {
		$processDefinition = ProcessDefinition::parseXmlString("
				<process-definition>
					<start-state>
						<transition to='play ball' />
					</start-state>
					<state name='play ball'>
						<event type='node-enter'>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
						</event>
						<exception-handler exception-class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\BatterException'>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher' />
						</exception-handler>
					</state>
				</process-definition>");
		
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
	}
	/**
	 *
	 * @todo might not be working??
	 */
	public function testDontCatchTheNonSpecifiedException() {
		$processDefinition = ProcessDefinition::parseXmlString("
				<process-definition>
					<start-state>
						<transition to='play ball' />
					</start-state>
					<state name='play ball'>
						<event type='node-enter'>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
						</event>
						<exception-handler exception-class='java.lang.RuntimeException'>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher' />
						</exception-handler>
					</state>
				</process-definition>");
		
		try {
			$pi = new ProcessInstance($processDefinition);
			$pi->signal();
		} catch ( DelegationException $e ) {
			var_dump($e);
			// $this->assertSame('BatterException.class', e . getCause() . getClass());
		}
	}
	
	public function testCatchWithTheSecondSpecifiedExceptionHandler()	{
		$processDefinition = ProcessDefinition::parseXmlString("
			<process-definition>
				<start-state>
					<transition to='play ball' />
				</start-state>
				<state name='play ball'>
					<event type='node-enter'>
						<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
					</event>
			<!-- the first exception-handler will not catch the BatterException -->
					<exception-handler exception-class='java.lang.RuntimeException'>
						<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher' />
					</exception-handler>
			<!-- but the second exception-handler will catch all -->
					<exception-handler>
						<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\SecondExceptionHandler' />
					</exception-handler>
				</state>
			</process-definition>");
	
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
		$this->assertEquals(1, sizeof(ExceptionHandlingTest::$executedActions));
		$this->assertSame('com\coherentnetworksolutions\pbpm\graph\exe\SecondExceptionHandler', get_class(ExceptionHandlingTest::$executedActions[0]));
	}
	
	public function testTwoActionsInOneExceptionHandler() {
		$processDefinition = ProcessDefinition::parseXmlString("
				<process-definition>
					<start-state>
						<transition to='play ball' />
					</start-state>
					<state name='play ball'>
						<event type='node-enter'>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
						</event>
						<exception-handler>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher' />
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\SecondExceptionHandler' />
						</exception-handler>
					</state>
				</process-definition>");
	
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
		$this->assertEquals(2, sizeof(ExceptionHandlingTest::$executedActions));
		$this->assertSame('com\coherentnetworksolutions\pbpm\graph\exe\Pitcher', get_class(ExceptionHandlingTest::$executedActions[0]));
		$this->assertSame('com\coherentnetworksolutions\pbpm\graph\exe\SecondExceptionHandler', get_class(ExceptionHandlingTest::$executedActions[1]));
	}
	
	public function testProcessDefinitionExceptionHandling() {
			$processDefinition = ProcessDefinition::parseXmlString("
					<process-definition>
						<start-state>
							<transition to='play ball' />
						</start-state>
						<state name='play ball'>
							<event type='node-enter'>
								<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
							</event>
						</state>
						<exception-handler>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher' />
						</exception-handler>
					</process-definition>");
	
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
		$this->assertEquals(1, sizeof(ExceptionHandlingTest::$executedActions));
	}
	
	public function testSuperStateExceptionHandling()	{
		$processDefinition = ProcessDefinition::parseXmlString("
				<process-definition>
					<start-state>
						<transition to='superstate/play ball' />
					</start-state>
					<super-state name='superstate'>
						<state name='play ball'>
							<event type='node-enter'>
								<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Batter' />
										</event>
						</state>
						<exception-handler>
							<action class='com\\coherentnetworksolutions\\pbpm\\graph\\exe\\Pitcher' />
						</exception-handler>
					</super-state>
				</process-definition>");
	
		$pi = new ProcessInstance($processDefinition);
		$pi->signal();
		$this->assertEquals(1, sizeof(ExceptionHandlingTest::$executedActions));
	}
}
class BatterException extends \Exception {
}
class Batter implements ActionHandler {
	public function execute(ExecutionContext $executionContext) {
		throw new BatterException();
	}
}
class Pitcher implements ActionHandler {
	public function execute(ExecutionContext $executionContext) {
		$this->exception = $executionContext->getException();
		ExceptionHandlingTest::$executedActions[] = $this;
	}
}
class RuntimeBatter implements ActionHandler {
	public function execute(ExecutionContext $executionContext) {
		throw new \RuntimeException("here i come");
	}
}
class SecondExceptionHandler implements ActionHandler {
	public function execute(ExecutionContext $executionContext) {
		ExceptionHandlingTest::$executedActions[] = $this;
	}
}
