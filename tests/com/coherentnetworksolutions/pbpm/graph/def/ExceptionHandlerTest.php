<?php 
namespace com\coherentnetworksolutions\pbpm\graph\def;
//TODO: Needs execution

use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;

class NoExceptionAction implements ActionHandler {
	public function execute(ExecutionContext $executionContext)  {}
}

class ThrowExceptionAction implements ActionHandler {
  	public function execute(ExecutionContext $executionContext)  {
		throw new \Exception("exception in action handler");
    }
}

class ThrowInnerExceptionAction implements ActionHandler {
 	public function execute(ExecutionContext $executionContext) {
		throw new \Exception("exception inside of exception handler");
	}
}

/**
 * 
 * @requires function NEEDS_EXE_FUNCTIONALITY
 *
 */
class ExceptionHandlerTest extends \PHPUnit_Framework_TestCase {

// 	/**
// 	 * @test
// 	 */
	
//   public function testExceptionHandlerThrowingException() {
//     $xml = "<?xml version='1.0' encoding='UTF-8'xX>" .
//         "<process-definition name='TestException'>" .
//         "   <start-state name='start'>" .
//         "      <transition to='end'>" .
//         "         <action class='com\coherentnetworksolutions\pbpm\graph\def\ThrowExceptionAction' />" .
//         "      </transition>" .
//         "   </start-state>   " .
//         "   <end-state name='end' />" .
//         "   <exception-handler>" .
//         "      <action class='com\coherentnetworksolutions\pbpm\graph\def\ThrowInnerExceptionAction' />" .
//         "   </exception-handler>" .
//         "</process-definition>";

//     $def = ProcessDefinition::parseXmlString($xml);
//     $pi = $def->createProcessInstance();

//     try {
//       $pi->signal();
//     }
//     catch (DelegationException $ex) {
//       // check that exception is thrown to the client nested in a DelegationException
//       $this->assertEquals("exception inside of exception handler", $ex->getCause()->getMessage());
//     }
//   }

//   public void testMissingExceptionHandlerClass() {
//     String xml = "<?xml version='1.0' encoding='UTF-8'X>" +
//         "<process-definition name='TestException'>" +
//         "   <start-state name='start'>" +
//         "      <transition to='end'>" +
//         "         <action class='" +
//         ThrowExceptionAction.class.getName() +
//         "' />" +
//         "      </transition>" +
//         "   </start-state>   " +
//         "   <end-state name='end' />" +
//         "   <exception-handler>" +
//         "      <action class='org.jbpm.graph.def.ExceptionHandlerTest$DOESNOTEXIST' />" +
//         "   </exception-handler>" +
//         "</process-definition>";

//     ProcessDefinition def = ProcessDefinition.parseXmlString(xml);
//     ProcessInstance pi = def.createProcessInstance();

//     try {
//       pi.getRootToken().signal();
//     }
//     catch (DelegationException ex) {
//       // check that exception is thrown to the client nested in a DelegationException
//       assertSame(ClassNotFoundException.class, ex.getCause().getClass());
//     }
//   }

//   public void testNoException() {
//     String xml = "<?xml version='1.0' encoding='UTF-8'X>" +
//         "<process-definition name='TestException'>" +
//         "   <start-state name='start'>" +
//         "      <transition to='end'>" +
//         "         <action class='" +
//         ThrowExceptionAction.class.getName() +
//         "' />" +
//         "      </transition>" +
//         "   </start-state>   " +
//         "   <end-state name='end' />" +
//         "   <exception-handler>" +
//         "      <action class='" +
//         NoExceptionAction.class.getName() +
//         "' />" +
//         "   </exception-handler>" +
//         "</process-definition>";

//     ProcessDefinition def = ProcessDefinition.parseXmlString(xml);
//     ProcessInstance pi = def.createProcessInstance();
//     pi.signal();

//     // exception is handled correctly
//     assertTrue("expected " + pi + " to have ended", pi.hasEnded());
//   }

//   /**
//    * If exception handlers are defined in multiple nodes, only the first one is triggered
//    * during one execution.
//    * 
//    * @see <a href="https://jira.jboss.org/browse/JBPM-2854">JBPM-2854</a>
//    */
//   public void testMultipleExceptionHandler() {	
//     String xml = "<?xml version='1.0' encoding='UTF-8'X	>" +
//         "<process-definition name='TestException'>" +
//         "   <start-state name='start'>" +
//         "      <transition to='node1' />" +
//         "   </start-state>   " +
//         "   <node name='node1'>" +        
//         "      <event type='node-enter'>" +
//         "         <script>executionContext.setVariable(\"count\", 0)</script>" +
//         "         <action class='" +
//         ThrowExceptionAction.class.getName() +
//         "' />" +
//         "      </event>" +
//         "      <exception-handler>" +
//         "         <script>executionContext.setVariable(\"count\", count + 1)</script>" +
//         "      </exception-handler>" +
//         "      <transition to='node2' />" +
//         "   </node>" +
//         "   <node name='node2'>" +
//         "      <event type='node-enter'>" +
//         "         <action class='" +
//         ThrowExceptionAction.class.getName() +
//         "' />" +
//         "      </event>" +
//         "      <exception-handler>" +
//         "         <script>executionContext.setVariable(\"count\", count + 1)</script>" +
//         "      </exception-handler>" +
//         "      <transition to='end' />" +
//         "   </node>" +
//         "   <end-state name='end' />" +
//         "</process-definition>";

//     ProcessDefinition def = ProcessDefinition.parseXmlString(xml);
//     ProcessInstance pi = def.createProcessInstance();

//     // should not throw DelegationException
//     pi.signal();

//     // two exceptions are handled
//     Integer count = (Integer) pi.getContextInstance().getVariable("count");
//     assertEquals(2, count.intValue());
//   }
}
