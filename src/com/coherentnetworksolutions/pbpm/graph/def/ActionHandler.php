<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;

interface ActionHandler {

    /**
     * @param ExecutionContext $executionContext
     * @throws Exception
     */
    function execute( ExecutionContext $executionContext );
}