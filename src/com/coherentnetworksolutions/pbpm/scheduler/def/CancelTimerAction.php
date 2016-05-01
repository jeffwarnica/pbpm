<?php
namespace com\coherentnetworksolutions\pbpm\scheduler\def;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;

/** @entity **/
class CancelTimerAction extends Action {

    private $timerName = null;

    public function read(\DOMElement $actionElement, JpdlXmlReader $jpdlReader) {
        $this->timerName = $actionElement->getAttribute("name");
        if ( $this->timerName == "" ) {
            $jpdlReader->addWarning("no 'name' specified in CancelTimerAction '{$actionElement}'");
        }
    }
    
    //   public void execute(ExecutionContext executionContext) throws Exception {
    //     SchedulerService schedulerService = (SchedulerService) Services.getCurrentService(Services.SERVICENAME_SCHEDULER);
    //     schedulerService.cancelTimersByName(timerName, executionContext.getToken());
    //   }
    public function getTimerName() {
        return $this->timerName;
    }

    public function setTimerName($timerName) {
        $this->timerName = $timerName;
    }
}