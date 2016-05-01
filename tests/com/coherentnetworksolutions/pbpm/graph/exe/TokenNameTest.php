<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;

class TokenNameTest extends \PHPUnit_Framework_TestCase {

  
  public function testFindRoot() {
    $pd = ProcessDefinition::parseXmlString("
      <process-definition>
        <start-state name='start'>
          <transition to='f1' />
    	</start-state>
    	<fork name='f1'>
			<transition to='a' name='a' />
    		<transition to='f2' name='f2' />
    	</fork>
    	<state name='a'>
    	</state>
    	<fork name='f2'>
			<transition to='b' name='b' />    		
    		<transition to='c' name='c' />
    	</fork>	
    	<state name='b'>
    	</state>
    	<state name='c'>
    	</state>
    </process-definition>
    		");
//       new String[]{"start-state start",
//                    "fork f1",
//                    "state a",
//                    "fork f2",
//                    "state b",
//                    "state c"}, 
//       new String[]{"start --> f1",
//                    "f1 --a--> a",
//                    "f1 --f2--> f2",
//                    "f2 --b--> b",
//                    "f2 --c--> c"});
    
    $pi = new ProcessInstance($pd);
    $pi->signal();

    // now we have the following tree of tokens
    // at the right, the full name of the $is presented.
    //
    // root-$  --> /
    //  +- a        --> /a
    //  +- f2       --> /f2
    //      +- b    --> /f2/b
    //      +- c    --> /f2/c
    
    $root = $pi->getRootToken();
    $tokenA = $root->getChild("a");
    $tokenF2 = $root->getChild("f2");
    $tokenF2B = $tokenF2->getChild("b");
    $tokenF2C = $tokenF2->getChild("c");
    
    $this->assertEquals("/", $root->getFullName());
    $this->assertEquals("/a", $tokenA->getFullName());
    $this->assertEquals("/f2/b", $tokenF2B->getFullName());
    $this->assertEquals("/f2/c", $tokenF2C->getFullName());
    
    $this->assertSame( $root, $pi->findToken( "/" ) );
    $this->assertSame( $root, $pi->findToken( "" ) );
    $this->assertSame( $root, $pi->findToken( "." ) );
    $this->assertSame( $root, $tokenA->findToken( ".." ) );
    $this->assertSame( $root, $tokenA->findToken( "../." ) );

    $this->assertSame( $tokenA, $pi->findToken( "/a" ) );
    $this->assertSame( $tokenA, $tokenF2C->findToken( "/a" ) );
    $this->assertSame( $tokenA, $pi->findToken( "a" ) );

    $this->assertSame( $tokenF2, $pi->findToken( "f2" ) );
    $this->assertSame( $tokenF2, $pi->findToken( "/f2" ) );
    $this->assertSame( $tokenF2, $tokenF2C->findToken( ".." ) );

    $this->assertSame( $tokenF2B, $pi->findToken( "f2/b" ) );
    $this->assertSame( $tokenF2B, $pi->findToken( "/f2/b" ) );

    $this->assertNull( $pi->findToken( null ) );
    $this->assertNull( $pi->findToken( "non-existing-token-name" ) );
    $this->assertNull( $pi->findToken( "/a/non-existing-token-name" ) );
    $this->assertNull( $pi->findToken( ".." ) );
    $this->assertNull( $pi->findToken( "/.." ) );
  }
}
