<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class EventsDbTest extends AbstractDbTestCase {

    /**
     * @test
     */
    public function testEventEventType() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <event type='process-start' />
                        </process-definition>");
        
        $processDefinition = $this->saveAndReload($processDefinition);
        $this->assertEquals("process-start", $processDefinition->getEvent("process-start")->getEventType());
    }

    /**
     * @test
     */
    public function testEventGraphElementProcessDefinition() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <event type='process-start' />
                            </process-definition>");
        
        $this->assertSame($processDefinition, $processDefinition->getEvent("process-start")->getGraphElement());
        $processDefinition = $this->saveAndReload($processDefinition);
        $this->assertSame($processDefinition, $processDefinition->getEvent("process-start")->getGraphElement());
    }

    /**
     * @test
     */
    public function testEventGraphElementNode() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='n'>
                            <event type='node-enter'/>
                          </node>
                        </process-definition>");
        
        $this->processDefinition = $this->saveAndReload($processDefinition);
        $this->assertSame($processDefinition->getNode("n"), $processDefinition->getNode("n")->getEvent("node-enter")->getGraphElement());
    }
	
	/**
	 * @test
	 * 
	 * @todo : reenable when doctrine sqlite isn't fucked
	 */
	public function testEventGraphElementTransition() {
		$processDefinition = ProcessDefinition::parseXmlString("<process-definition>
                              <node name='n'>
                                <transition name='t' to='n'>
                                  <action class='unimportant'/>
                                </transition>
                              </node>
                            </process-definition>");
		
		$processDefinition = $this->saveAndReload($processDefinition);
		
		$t = $processDefinition->getNode("n")->getLeavingTransition("t");
		$this->assertSame($t, $t->getEvent("transition")->getGraphElement());
	}
    

    /**
     * @test
     * @todo: reenable when doctrine sqlite isn't fucked
     * 
     */
    public function testEventActions() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <event type='process-start'>
                            <action class='a'/>
                            <action class='b'/>
                            <action class='c'/>
                            <action class='d'/>
                          </event>
                        </process-definition>");
        
        $processDefinition = $this->saveAndReload($processDefinition);
        
        $this->assertEquals("a", 
                        $processDefinition->getEvent("process-start")->getActions()->first()->getActionDelegation()->getClassName());
        $this->assertEquals("b", $processDefinition->getEvent("process-start")->getActions()->next()->getActionDelegation()->getClassName());
        $this->assertEquals("c", $processDefinition->getEvent("process-start")->getActions()->next()->getActionDelegation()->getClassName());
        $this->assertEquals("d", $processDefinition->getEvent("process-start")->getActions()->next()->getActionDelegation()->getClassName());
    }
}