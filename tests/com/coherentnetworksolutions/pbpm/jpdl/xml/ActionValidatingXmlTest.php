<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\Event;

require_once("AbstractXMLTestCase.php");

class ActionValidatingXMLTest extends AbstractXmlTestCase {
    
    public $schemaNamespace = "xmlns=\"urn:jbpm.org:jpdl-3.1\"";

//     private $log = LogFactory.getLog(ActionValidatingXmlTest.class);
    
//     /**
//      * @test
//      * @expectedException Exception
//      */
//     public function testInvalidXML() {
//             ProcessDefinition::parseXmlString(
//                             "<process-definition " .  $this->schemaNamespace ." name='pd' >" .
//                             "  <event type='process-start'>" .
//                             "    <action xyz='2' class='one'/>" .
//                             "    <action class='two'/>" .
//                             "    <action class='three'/>" .
//                             "  </event>" .
//                             "</process-definition>"
//             );
//     }
    
    /**
     * @test
     */
    public function testReadProcessDefinitionAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition " . $this->schemaNamespace . " name='pd'>" .
                        "  <event type='process-start' name='IAMBOB'>" .
                        "    <action class='one'/>" .
                        "    <action class='two'/>" .
                        "    <action class='three'/>" .
                        "  </event>" .
                        "</process-definition>"
        );
    
    
        $event = $processDefinition->getEvent("process-start");
        $this->assertEquals(3, $event->getActions()->count());
        //@todo Delegation's
        $this->assertEquals("one", $event->getActions()->get(0)->getActionDelegation()->getClassName());
        $this->assertEquals("two", $event->getActions()->get(1)->getActionDelegation()->getClassName());
        $this->assertEquals("three", $event->getActions()->get(2)->getActionDelegation()->getClassName());
    
    }
    
    public function testReadActionConfigType() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <action name='burps' class='org.foo.Burps' config-type='bean' />
                        </process-definition>"
        );
    
        $this->assertEquals("bean", $processDefinition->getAction("burps")->getActionDelegation()->getConfigType() );
    }
    
    public function testReadActionXmlConfiguration() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <action name='burps' class='org.foo.Burps' config-type='bean'>
                            <id>63</id>
                            <greeting>aloha</greeting>
                          </action>
                        </process-definition>"
        );
    
        $action = $processDefinition->getAction("burps");
        $instantiatableDelegate = $action->getActionDelegation();
        $this->assertContains("<id>63</id>", $instantiatableDelegate->getConfiguration());
        $this->assertContains("<greeting>aloha</greeting>", $instantiatableDelegate->getConfiguration());
    }
    
    public function testReadActionTextConfiguration() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <action name='burps' class='org.foo.Burps' config-type='constructor'>
                            a piece of configuration text
                          </action>
                        </process-definition>"
        );    
        $action = $processDefinition->getAction("burps");
        $instantiatableDelegate = $action->getActionDelegation();
        $this->assertContains("a piece of configuration text", $instantiatableDelegate->getConfiguration());
    }
    
    public function testReadActionAcceptPropagatedEventsDefault() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <action name='burps' class='org.foo.Burps' />
                        </process-definition>"
        );
    
        $action = $processDefinition->getAction("burps");
        $this->assertTrue($action->acceptsPropagatedEvents());
    }
    
    public function testReadActionAcceptPropagatedEventsTrue() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <action name='burps' class='org.foo.Burps' accept-propagated-events='true' />
                        </process-definition>"
        );
    
        $action = $processDefinition->getAction("burps");
        $this->assertTrue($action->acceptsPropagatedEvents());
    }
    
    public function testReadActionAcceptPropagatedEventsFalse() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <action name='burps' class='org.foo.Burps' accept-propagated-events='false' />
                        </process-definition>"
        );
    
        $action = $processDefinition->getAction("burps");
        $this->assertFalse($action->acceptsPropagatedEvents());
    }
    
    public function testReadNodeActionName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <node name='a'>
                            <action class='one'/>
                            <event type='node-enter'>
                              <action name='burps' class='org.foo.Burps'/>
                            </event>
                          </node>
                        </process-definition>"
        );
        $burps = $processDefinition->getNode("a")->getEvent("node-enter")->getActions()->get(0);
        $this->assertEquals("burps", $burps->getName());
    }
    
    public function testReadNodeEnterAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <node name='a'>
                            <action class='one'/>
                            <event type='node-enter'>
                              <action class='org.foo.Burps'/>
                            </event>
                          </node>
                        </process-definition>"
        );
        $this->assertEquals("org.foo.Burps", $processDefinition->getNode("a")->getEvent("node-enter")->getActions()->get(0)->getActionDelegation()->getClassName());
    }
    
    public function testParseAndWriteOfNamedEventActions() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd_testParseAndWriteOfNamedEventActions'>
                          <node name='a'>
                            <action class='one'>Random <stuff/> here</action>
                            <event type='node-enter'>
                              <action name='burps' class='org.foo.Burps'/>
                            </event>
                          </node>
                        </process-definition>"
        );
        
        $burps = $processDefinition->getNode("a")->getEvent("node-enter")->getActions()->get(0);
        $this->assertSame($burps, $processDefinition->getAction("burps"));

        $processDefinitionElement = $this->toXmlAndParse($processDefinition, "/process-definition" );
        $this->assertEquals(0, sizeof($this->getDirectChildrenByTagName($processDefinitionElement, "action")));
    }
    
    public function testParseStateAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <state name='a'>
                            <event type='node-enter'>
                              <action class='org.foo.Burps' config-type='constructor'>
                                this text should be passed in the constructor
                              </action>
                            </event>
                          </state>
                        </process-definition>"
        );
    
        $node = $processDefinition->getNode("a");
        $event = $node->getEvent("node-enter");
        $action = $event->getActions()->first();
        $instantiatableDelegate = $action->getActionDelegation();
        $this->assertEquals("org.foo.Burps", $instantiatableDelegate->getClassName());
        $this->assertEquals("constructor", $instantiatableDelegate->getConfigType());
        $this->assertContains("this text should be passed in the constructor",$instantiatableDelegate->getConfiguration() );
    }
    
    public function testParseTransitionAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                          <state name='a'>
                            <transition to='b'>
                              <action class='org.foo.Burps'/>
                            </transition>
                          </state>
                          <state name='b' />
                        </process-definition>"
        );
    
        $node = $processDefinition->getNode("a");
        $this->assertEquals( 1, sizeof($node->getLeavingTransitionsMap()) );
        $transition = $node->getDefaultLeavingTransition();
        $event = $transition->getEvent(Event::EVENTTYPE_TRANSITION);
        $action = $event->getActions()->first();
        $instantiatableDelegate = $action->getActionDelegation();
        $this->assertEquals("org.foo.Burps", $instantiatableDelegate->getClassName());
    }
    
    /**
     * @test
     */
    public function testParseReferencedAction() {
        try
        {
            $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition {$this->schemaNamespace} name='pd'>
                              <node name='a'>
                                <action class='one'/>
                                <transition to='b'>
                                  <action ref-name='scratch'/>
                                </transition>
                              </node>
                              <node name='b'>
                                <action class='two'/>
                              </node>
                              <action name='scratch' class='com.itch.Scratch' />
                            </process-definition>"
            );
        }
        catch(\Exception $je) {
            $this->fail("XML did not pass validation as expected:\n" . $je->__toString());
        }
    
        $node = $processDefinition->getNode("a");
        $transition = $node->getDefaultLeavingTransition();
        $event = $transition->getEvent(Event::EVENTTYPE_TRANSITION);
        $transitionAction = $event->getActions()->first();
    
        $processAction = $processDefinition->getAction("scratch");
        $this->assertEquals("scratch", $processAction->getName());
        $this->assertSame($processAction, $transitionAction->getReferencedAction());
    
        $instantiatableDelegate = $processAction->getActionDelegation();
        $this->assertEquals("com.itch.Scratch", $instantiatableDelegate->getClassName());
    }
    /**
     * @test
     */
    public function testParseActionWithoutClass() {
        $xml = "<process-definition {$this->schemaNamespace} name='pd'>
                          <node name='a'>
                            <action class='one'/>
                            <event type='node-enter'>
                              <action />
                            </event>
                          </node>
                        </process-definition>";
        $jpdlReader = new JpdlXmlReader($xml);
        $jpdlReader->readProcessDefinition();
        $this->assertTrue(($jpdlReader->problemsWarn > 0));
    }
    /**
     * @test
     */
    public function testParseActionWithInvalidReference() {
        $xml = "<process-definition {$this->schemaNamespace} name='pd'>
                          <node name='a'>
                            <action class='one'/>
                            <event type='node-enter'>
                              <action ref-name='non-existing-action-name'/>
                            </event>
                          </node>
                        </process-definition>";
        $jpdlReader = new JpdlXmlReader($xml);
        $jpdlReader->readProcessDefinition();
        $this->assertTrue(($jpdlReader->problemsWarn > 0));
    }
    
}