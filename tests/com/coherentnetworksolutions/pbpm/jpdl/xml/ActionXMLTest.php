<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
use com\coherentnetworksolutions\pbpm\instantiation\Delegation;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\node\State;

require_once ("AbstractXMLTestCase.php");
class ActionXmlTest extends AbstractXmlTestCase {

    /**
     * @test
     */
    public function testReadProcessDefinitionAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>" . "  <event type='node-enter'>" . "    <action class='one'/>" . "    <action class='two'/>" .
                                         "    <action class='three'/>" . "  </event>" . "</process-definition>");
        $event = $processDefinition->getEvent("node-enter");
        $this->assertEquals(3, $event->getActions()->count());
        
        $this->assertEquals("one", $event->getActions()->get(0)->getActionDelegation()->getClassName());
        $this->assertEquals("two", $event->getActions()->get(1)->getActionDelegation()->getClassName());
        $this->assertEquals("three", $event->getActions()->get(2)->getActionDelegation()->getClassName());
    }

    /**
     * @test
     */
    public function testWriteProcessDefinitionAction() {
        $processDefinition = new ProcessDefinition();
        $event = new Event("node-enter");
        $processDefinition->addEvent($event);
        $event->addAction(new Action(new Delegation("one")));
        $event->addAction(new Action(new Delegation("two")));
        $event->addAction(new Action(new Delegation("three")));
        
        /**
         * @var \DOMElement
         */
        $eventElement = $this->toXmlAndParse($processDefinition, "/process-definition/event");
        $actionElements = $eventElement->getElementsByTagName("action");
        $this->assertEquals(3, $actionElements->length);
        $this->assertEquals("one", $actionElements->item(0)->getAttribute("class"));
        $this->assertEquals("two", $actionElements->item(1)->getAttribute("class"));
        $this->assertEquals("three", $actionElements->item(2)->getAttribute("class"));
    }

    /**
     * @test
     */
    public function testReadActionConfigType() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                          <action name='burps' class='org.foo.Burps' config-type='bean' />
                        </process-definition>");
        
        $this->assertEquals("bean", $processDefinition->getAction("burps")->getActionDelegation()->getConfigType());
    }

    /**
     * @test
     */
    public function testWriteActionConfigType() {
        $processDefinition = new ProcessDefinition();
        $actionDelegate = new Delegation("one");
        $actionDelegate->setConfigType("bean");
        $action = new Action($actionDelegate);
        $action->setName("a");
        $processDefinition->addAction($action);
        $actionElement = $this->toXmlAndParse($processDefinition, "/process-definition/action");
        // print $actionElement->ownerDocument->saveXML();
        $this->assertEquals("bean", $actionElement->getAttribute("config-type"));
    }

    /**
     * @test
     */
    public function testReadActionXmlConfiguration() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <action name='burps' class='org.foo.Burps' config-type='bean'>
                                <id>63</id>
                                <greeting>aloha</greeting>
                              </action>
                            </process-definition>");
        
        $action = $processDefinition->getAction("burps");
        $instantiatableDelegate = $action->getActionDelegation();
        $this->assertContains("<id>63</id>", $instantiatableDelegate->getConfiguration());
        $this->assertContains("<greeting>aloha</greeting>", $instantiatableDelegate->getConfiguration());
    }

    /**
     * @test
     */
    public function testWriteActionXmlConfiguration() {
        $processDefinition = new ProcessDefinition();
        $actionDelegate = new Delegation("one");
        $actionDelegate->setConfiguration("<id>63</id><greeting>aloha</greeting>");
        $action = new Action($actionDelegate);
        $action->setName("a");
        $processDefinition->addAction($action);
        $actionElement = $this->toXmlAndParse($processDefinition, "/process-definition/action");
        $this->assertEquals("63", $actionElement->getElementsByTagName("id")->item(0)->textContent);
        $this->assertEquals("aloha", $actionElement->getElementsByTagName("greeting")->item(0)->textContent);
    }

    /**
     * @test
     */
    public function testReadActionTextConfiguration() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <action name='burps' class='org.foo.Burps' config-type='constructor'>
                                a piece of configuration text
                              </action>
                            </process-definition>");
        $action = $processDefinition->getAction("burps");
        $instantiatableDelegate = $action->getActionDelegation();
        $this->assertContains("a piece of configuration text", $instantiatableDelegate->getConfiguration());
    }

    /**
     * @test
     */
    public function testWriteActionTextConfiguration() {
        $processDefinition = new ProcessDefinition();
        $actionDelegate = new Delegation("one");
        $actionDelegate->setConfiguration("a piece of configuration text");
        $actionDelegate->setConfigType("constructor");
        $action = new Action($actionDelegate);
        $action->setName("a");
        $processDefinition->addAction($action);
        $actionElement = $this->toXmlAndParse($processDefinition, "/process-definition/action");
        $this->assertEquals("a piece of configuration text", $actionElement->textContent);
    }

    /**
     * @test
     */
    public function testReadActionAcceptPropagatedEventsDefault() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <action name='burps' class='org.foo.Burps' />
                            </process-definition>");
        
        $action = $processDefinition->getAction("burps");
        $this->assertTrue($action->acceptsPropagatedEvents());
    }

    /**
     * @test
     */
    public function testReadActionAcceptPropagatedEventsTrue() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <action name='burps' class='org.foo.Burps' accept-propagated-events='true' />
                            </process-definition>");
        
        $action = $processDefinition->getAction("burps");
        $this->assertTrue($action->acceptsPropagatedEvents());
    }

    /**
     * @test
     */
    public function testReadActionAcceptPropagatedEventsFalse() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <action name='burps' class='org.foo.Burps' accept-propagated-events='false' />
                            </process-definition>");
        $action = $processDefinition->getAction("burps");
        $this->assertFalse($action->acceptsPropagatedEvents());
    }

    /**
     * Generated XML should not so much as have the accept-propagated-events attribute
     * @test
     */
    public function testWriteActionAcceptPropagatedEventsDefault() {
        $processDefinition = new ProcessDefinition();
        $actionDelegate = new Delegation("one");
        $action = new Action($actionDelegate);
        $action->setName("a");
        $processDefinition->addAction($action);
        $actionElement = $this->toXmlAndParse($processDefinition, "/process-definition/action");
        $this->assertFalse($actionElement->hasAttribute("accept-propagated-events"));
    }

    /**
     * Generated XML should not so much as have the accept-propagated-events attribute
     * @test
     */
    public function testWriteActionAcceptPropagatedEventsTrue() {
        $processDefinition = new ProcessDefinition();
        $actionDelegate = new Delegation("one");
        $action = new Action($actionDelegate);
        $action->setName("a");
        $action->setPropagationAllowed(true);
        $processDefinition->addAction($action);
        $actionElement = $this->toXmlAndParse($processDefinition, "/process-definition/action");
        $this->assertFalse($actionElement->hasAttribute("accept-propagated-events"));
    }

    /**
     * Generated XML should have attribute "accept-propagated-events" set to literal string "false"
     * @test
     */
    public function testWriteActionAcceptPropagatedEventsFalse() {
        $processDefinition = new ProcessDefinition();
        $actionDelegate = new Delegation("one");
        $action = new Action($actionDelegate);
        $action->setName("a");
        $action->setPropagationAllowed(false);
        $processDefinition->addAction($action);
        $actionElement = $this->toXmlAndParse($processDefinition, "/process-definition/action");
        $this->assertEquals("false", $actionElement->getAttribute("accept-propagated-events"));
    }

    /**
     * @test
     */
    public function testReadNodeActionName() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <node name='a'>
                                <event type='node-enter'>
                                  <action name='burps' class='org.foo.Burps'/>
                                </event>
                              </node>
                            </process-definition>");
        $burps = $processDefinition->getNode("a")->getEvent("node-enter")->getActions()->first();
        $this->assertEquals("burps", $burps->getName());
    }

    /**
     * @test
     */
    public function testWriteNodeActionName() {
        $processDefinition = new ProcessDefinition();
        $node = $processDefinition->addNode(new Node());
        $instantiatableDelegate = new Delegation();
        $instantiatableDelegate->setClassName("com.foo.Fighting");
        $node->setAction(new Action($instantiatableDelegate));
        
        $actionElement = $this->toXmlAndParse($processDefinition, "/process-definition/node/action");
        $this->assertNotNull($actionElement);
        $this->assertEquals("action", $actionElement->nodeName);
        $this->assertEquals("com.foo.Fighting", $actionElement->getAttribute("class"));
    }

    /**
     * @test
     */
    public function testReadNodeEnterAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <node name='a'>
                                <event type='node-enter'>
                                  <action class='org.foo.Burps'/>
                                </event>
                              </node>
                            </process-definition>");
        $this->assertEquals("org.foo.Burps", 
                        $processDefinition->getNode("a")->getEvent("node-enter")->getActions()->first()->getActionDelegation()->getClassName());
    }

    /**
     * @test
     */
    public function testWriteNodeEnterAction() {
        $processDefinition = new ProcessDefinition();
        $node = $processDefinition->addNode(new Node());
        $instantiatableDelegate = new Delegation();
        $instantiatableDelegate->setClassName("com.foo.Fighting");
        $node->addEvent(new Event("node-enter"))->addAction(new Action($instantiatableDelegate));
        $element = $this->toXmlAndParse($processDefinition, "/process-definition/node[1]/event[1]");
        
        $this->assertNotNull($element);
        $this->assertEquals("event", $element->nodeName);
        $this->assertEquals("node-enter", $element->getAttribute("type"));
        $this->assertEquals(1, $element->getElementsByTagName("action")->length);
        
        $element = $element->getElementsByTagName("action")->item(0);
        $this->assertNotNull($element);
        $this->assertEquals("action", $element->nodeName);
        $this->assertEquals("com.foo.Fighting", $element->getAttribute("class"));
    }

    /**
     * @test
     */
    public function testParseAndWriteOfNamedEventActions() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <node name='a'>
                                <event type='node-enter'>
                                  <action name='burps' class='org.foo.Burps'/>
                                </event>
                              </node>
                            </process-definition>");
        $burps = $processDefinition->getNode("a")->getEvent("node-enter")->getActions()->first();
        $this->assertSame($burps, $processDefinition->getAction("burps"));
        $processDefinitionElement = $this->toXmlAndParse($processDefinition, "/process-definition");
        $this->assertEquals(0, sizeof($this->getDirectChildrenByTagName($processDefinitionElement, "action")));
    }

    /**
     * @test
     */
    public function testParseStateAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <state name='a'>
                                <event type='node-enter'>
                                  <action class='org.foo.Burps' config-type='constructor-text'>
                                    this text should be passed in the constructor
                                  </action>
                                </event>
                              </state>
                            </process-definition>");
        
        $node = $processDefinition->getNode("a");
        $event = $node->getEvent("node-enter");
        $action = $event->getActions()->first();
        $instantiatableDelegate = $action->getActionDelegation();
        $this->assertEquals("org.foo.Burps", $instantiatableDelegate->getClassName());
        $this->assertEquals("constructor-text", $instantiatableDelegate->getConfigType());
        $this->assertContains("this text should be passed in the constructor", $instantiatableDelegate->getConfiguration());
    }

    /**
     * @test
     */
    public function testParseTransitionAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <state name='a'>
                                <transition to='b'>
                                  <action class='org.foo.Burps'/>
                                </transition>
                              </state>
                              <state name='b' />
                            </process-definition>");
        
        $node = $processDefinition->getNode("a");
        $this->assertEquals(1, sizeof($node->getLeavingTransitionsMap()));
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
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <node name='a'>
                                <transition to='b'>
                                  <action ref-name='scratch'/>
                                </transition>
                              </node>
                              <node name='b' />
                              <action name='scratch' class='com.itch.Scratch' />
                            </process-definition>");
        
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
        $xml = "<process-definition>
                              <node>
                                <event type='node-enter'>
                                  <action />
                                </event>
                              </node>
                            </process-definition>";
        $jpdlReader = new JpdlXmlReader($xml);
        $jpdlReader->readProcessDefinition();
        $this->assertTrue(sizeof($jpdlReader->problemsWarn) > 0);
    }

    /**
     * @test
     */
    public function testParseActionWithInvalidReference() {
        $xml = "<process-definition>
                              <node>
                                <event type='node-enter'>
                                  <action ref-name='non-existing-action-name'/>
                                </event>
                              </node>
                            </process-definition>";
        $jpdlReader = new JpdlXmlReader($xml);
        $jpdlReader->readProcessDefinition();
        $this->assertTrue(sizeof($jpdlReader->problemsWarn) > 0);
    }

    /**
     * @test
     */
    public function testWriteTransitionAction() {
        $processDefinition = ProcessDefinition::parseXmlString(
                        "<process-definition>
                              <state name='a'>
                                <transition to='b' />
                              </state>
                              <state name='b' />
                            </process-definition>");
        $transition = $processDefinition->getNode("a")->getDefaultLeavingTransition();
        
        $instantiatableDelegate = new Delegation();
        $instantiatableDelegate->setClassName("com.foo.Fighting");
        $transition->addEvent(new Event(Event::EVENTTYPE_TRANSITION))->addAction(new Action($instantiatableDelegate));
        $element = $this->toXmlAndParse($processDefinition, "/process-definition/state/transition");
        
        $this->assertNotNull($element);
        $this->assertEquals("transition", $element->nodeName);
        $this->assertEquals(1, $element->getElementsByTagName("action")->length);
        
        $element = $element->getElementsByTagName("action")->item(0);
        $this->assertNotNull($element);
        $this->assertEquals("action", $element->nodeName);
        $this->assertEquals("com.foo.Fighting", $element->getAttribute("class"));
    }

    /**
     * @test
     */
    public function testWriteConfigurableAction() {
        $processDefinition = new ProcessDefinition();
        $state = $processDefinition->addNode(new State("a"));
        $instantiatableDelegate = new Delegation();
        $instantiatableDelegate->setClassName("com.foo.Fighting");
        $instantiatableDelegate->setConfigType("bean");
        $instantiatableDelegate->setConfiguration("<id>4</id><greeting>aloha</greeting>");
        $state->addEvent(new Event("node-enter"))->addAction(new Action($instantiatableDelegate));
        $element = $this->toXmlAndParse($processDefinition, "/process-definition/state[1]/event[1]/action[1]");
        
        $this->assertNotNull($element);
        $this->assertEquals("action", $element->nodeName);
        $this->assertEquals("bean", $element->getAttribute("config-type"));
        $this->assertEquals("4", $element->getElementsByTagName("id")->item(0)->textContent);
        $this->assertEquals("aloha", $element->getElementsByTagName("greeting")->item(0)->textContent);
    }

    /**
     * @test
     */
    public function testWriteReferenceAction() {
        $processDefinition = new ProcessDefinition();
        
        // add a global action with name 'pina colada'
        $instantiatableDelegate = new Delegation();
        $instantiatableDelegate->setClassName("com.foo.Fighting");
        $instantiatableDelegate->setConfigType("bean");
        $instantiatableDelegate->setConfiguration("<id>4</id><greeting>aloha</greeting>");
        $action = new Action();
        $action->setName("pina colada");
        $action->setActionDelegation($instantiatableDelegate);
        $processDefinition->addAction($action);
        
        // now create a reference to it from event node-enter on state 'a'
        $state = $processDefinition->addNode(new State());
        $refAction = new Action();
        $refAction->setReferencedAction($action);
        $state->addEvent(new Event(Event::EVENTTYPE_NODE_ENTER))->addAction($refAction);
        
        $this->toXmlAndParse($processDefinition, "/process-definition/state[1]/event[1]/action[1]");
    }
}