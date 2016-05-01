<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class ExceptionHandlerDbTest extends AbstractDbTestCase {

    /**
     * @test
     */
    public function testExceptionClassName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <exception-handler exception-class='org.coincidence.FatalAttractionException' />
                        </process-definition>");
        
        $processDefinition = $this->saveAndReload($processDefinition);
        
        $exceptionHandler = $processDefinition->getExceptionHandlers()->first();
        $this->assertNotNull($exceptionHandler);
        $this->assertEquals("org.coincidence.FatalAttractionException", $exceptionHandler->getExceptionClassName());
    }

    /**
     * @test
     */
    public function testExceptionHandlerProcessDefinition() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <exception-handler exception-class='org.coincidence.FatalAttractionException' />
                        </process-definition>");
        
        $processDefinition = $this->saveAndReload($processDefinition);
        $exceptionHandler = $processDefinition->getExceptionHandlers()->first();
        $this->assertSame($processDefinition, $exceptionHandler->getGraphElement());
    }

    /**
     * @test
     */
    public function testExceptionHandlerNode() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='a'>
                            <exception-handler exception-class='org.coincidence.FatalAttractionException' />
                          </node>
                        </process-definition>");
        
        $processDefinition = $this->saveAndReload($processDefinition);
        
        $node = $processDefinition->getNode("a");
        $exceptionHandler = $node->getExceptionHandlers()->first();
        $this->assertSame($node, $exceptionHandler->getGraphElement());
    }

    /**
     * @test
     */
    public function testExceptionHandlerTransition() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <node name='a'>
                            <transition name='self' to='a'>
                              <exception-handler exception-class='org.coincidence.FatalAttractionException' />
                            </transition>
                          </node>
                        </process-definition>");
        
        $processDefinition = $this->saveAndReload($processDefinition);
        
        $transition = $processDefinition->getNode("a")->getLeavingTransition("self");
        $exceptionHandler = $transition->getExceptionHandlers()->first();
        $this->assertSame($transition, $exceptionHandler->getGraphElement());
    }

    /**
     * @test
     */
    public function testExceptionHandlerActions() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <exception-handler exception-class='org.coincidence.FatalAttractionException'>
                            <action class='one' />
                            <action class='two' />
                            <action class='three' />
                            <action class='four' />
                          </exception-handler>
                        </process-definition>");
        
        $processDefinition = $this->saveAndReload($processDefinition);
        
        $exceptionHandler = $processDefinition->getExceptionHandlers()->first();
        $actions = $exceptionHandler->getActions();
        $this->assertEquals("one", $actions->first()->getActionDelegation()->getClassName());
        $this->assertEquals("two", $actions->next()->getActionDelegation()->getClassName());
        $this->assertEquals("three", $actions->next()->getActionDelegation()->getClassName());
        $this->assertEquals("four", $actions->next()->getActionDelegation()->getClassName());
    }
}