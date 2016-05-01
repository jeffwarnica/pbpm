<?php
namespace com\coherentnetworksolutions\pbpm\jpdl\xml;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;

interface Parsable {

    public function read(\DOMElement $element, JpdlXmlReader $jpdlReader);

    public function write(\DOMElement $element);
}
