<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;

class AbstractXmlTestCase extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function fuckOffPhpUnit() {
        $this->assertTrue(true);
    }
    
    /**
     * @return \DOMElement
     * @throws Exception
     */
    static function toXmlAndParse(ProcessDefinition $processDefinition, $xpathExpression = null, $namespace = null) {
        $jpdlWriter = new JpdlXmlWriter();
        if ($namespace) {
            $jpdlWriter->setUseNamespace(true);
        }
        
        $doc = $jpdlWriter->write( $processDefinition );
        
        if ($xpathExpression != "") {
            $xpath = new \DOMXPath($doc);
            $nodeList = $xpath->evaluate($xpathExpression);
            $docElement = $nodeList->item(0);
        } else {
            $docElement = $doc->documentElement;
        }
        return $docElement; 
        
    }


    static function printXml(ProcessDefinition $processDefinition) {
        $xml =  self::toXmlAndParse($processDefinition);
        print $xml->ownerDocument->saveXML();
    }
    
    /**
     * Stock DOMElement::getElementsByTagName goes deep. This just cares about direct children
     *
     * @param \DOMElemenet $elem
     * @param array<\DOMElemenet> $tagName
     */
    protected function getDirectChildrenByTagName(\DOMElement $elem, $tagName) {
        $okChildren = [];
        foreach ($elem->childNodes as $possibleGoodElement) {
            if ($possibleGoodElement->nodeName !== $tagName) { continue; }
            $okChildren[] = $possibleGoodElement;
        }
        return $okChildren;
    }

}
