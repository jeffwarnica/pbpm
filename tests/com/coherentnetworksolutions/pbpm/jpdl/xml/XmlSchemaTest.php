<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\jpdl\JpdlException;

class XmlSchemaTest extends \PHPUnit_Framework_TestCase {

    /**
     * parses the xml file in the subdir 'files' that corresponds
     * with the test method name.
     */
    public function parseXmlForThisMethod() {
        $fileName = dirname(__FILE__) . "/files/" . $this->getName() . ".xml";
        
        $xml = file_get_contents($fileName);
        
        return ProcessDefinition::parseXmlString($xml);
    }

    /**
     * @test
     * @expectedException com\coherentnetworksolutions\pbpm\jpdl\JpdlException
     */
    public function testInvalidXml() {
        $this->parseXmlForThisMethod();
    }

    /**
     * @test
     */
    public function testNoSchemaReference() {
        // without a reference to the schema, the process definition is
        // not validated and parsing succeeds
        $this->parseXmlForThisMethod();
    }

//     /**
//      * @test
//      * @expectedException com\coherentnetworksolutions\pbpm\jpdl\JpdlException
//      */
//     public function testSimpleSchemaReference() {
//         $this->parseXmlForThisMethod();
//     }

//     /**
//      * @test
//      * @expectedException com\coherentnetworksolutions\pbpm\jpdl\JpdlException
//      */
//     public function testProcessDefinitionWithSchemaLocation() {
//         $this->parseXmlForThisMethod();
//     }

//     /**
//      * @test
//      * @expectedException com\coherentnetworksolutions\pbpm\jpdl\JpdlException
//      */
//     public function testMultipleNamespaces() {
//         $this->parseXmlForThisMethod();
//     }

//     /**
//      * @test
//      * @expectedException com\coherentnetworksolutions\pbpm\jpdl\JpdlException
//      */
//     public function testInvalidProcessDefinitionAttribute() {
//         $this->parseXmlForThisMethod();
//     }

//     /**
//      * @test
//      * @expectedException com\coherentnetworksolutions\pbpm\jpdl\JpdlException
//      */
//     public function testInvalidProcessDefinitionContent() {
//         $this->parseXmlForThisMethod();
//     }

    /**
     * @test
     * @expectedException com\coherentnetworksolutions\pbpm\jpdl\JpdlException
     */
    public function testTwoStartStates() {
        $this->parseXmlForThisMethod();
    }
    
    public function testAction() {$this->parseXmlForThisMethod();}
    public function testDecision() {$this->parseXmlForThisMethod();}
    public function testEvent() {$this->parseXmlForThisMethod();}
    public function testStartState() {$this->parseXmlForThisMethod();}
    public function testTask() {$this->parseXmlForThisMethod();}
    public function testExceptionHandler() {$this->parseXmlForThisMethod();}
    public function testEndState() {$this->parseXmlForThisMethod();}
    public function testScript() {$this->parseXmlForThisMethod();}
}