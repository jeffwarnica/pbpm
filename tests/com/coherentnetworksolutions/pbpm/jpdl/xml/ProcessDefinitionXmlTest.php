<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;

require_once ("AbstractXMLTestCase.php");
class ProcessDefinitionXmlTest extends AbstractXmlTestCase {

    /**
     * @test
     */
    public function testParseProcessDefinition() {
        /**
         * @var ProcessDefinition
         */
        $processDefinition = ProcessDefinition::parseXmlString("<process-definition />");
        $this->assertNotNull($processDefinition);
        //         $this->$processDefinition);
    }

    /**
     * @test
     */
    public function testParseProcessDefinitionNonUTFEncoding() {
        $file = file_get_contents(dirname(__FILE__) . "/encodedprocess.xml");
        $processDefinition = ProcessDefinition::parseXmlString($file);
        $this->assertEquals("español", $processDefinition->getName());
    }

    /**
     * @test
     */
    public function testParseProcessDefinitionName() {
        $processDefinition = ProcessDefinition::parseXmlString("<process-definition name='make coffee' />");
        $this->assertEquals("make coffee", $processDefinition->getName());
    }

    /**
     * @test
     */
    public function testWriteProcessDefinition() {
        $processDefinition = new ProcessDefinition();
        $element = $this->toXmlAndParse($processDefinition);
        $this->assertNotNull($element);
        $this->assertEquals("process-definition", $element->nodeName);
        $this->assertFalse($element->hasAttributes());
    }

    /**
     * @test
     */
    public function testWriteProcessDefinitionName() {
        $processDefinition = new ProcessDefinition("myprocess");
        $element = $this->toXmlAndParse($processDefinition);
        $this->assertNotNull($element);
        $this->assertEquals("process-definition", $element->nodeName);
        $this->assertEquals("myprocess", $element->getAttribute("name"));
        $this->assertEquals(1, $element->attributes->length);
    }
}