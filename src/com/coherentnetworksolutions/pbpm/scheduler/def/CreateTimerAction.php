<?php
namespace com\coherentnetworksolutions\pbpm\scheduler\def;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;

/** @entity **/
class CreateTimerAction extends Action {
    
    //   static BusinessCalendar businessCalendar = new BusinessCalendar(); 
    public $timerName = null;

    public $dueDate = null;

    public $repeat = null;

    public $transitionName = null;

    public $timerAction = null;

    public function read(\DOMElement $actionElement, JpdlXmlReader $jpdlReader) {
        $this->log->debug("cta::read(actionElement->nodeName: {$actionElement->nodeName}) ");
        $this->timerName = $actionElement->getAttribute("name");
        $this->timerAction = $jpdlReader->readSingleAction($actionElement);
        
        $this->dueDate = $actionElement->getAttribute("duedate");
        if ( $this->dueDate == "" ) {
            $jpdlReader->addWarning("no duedate specified in create timer action '{$actionElement->nodeName}'");
        }
        $this->repeat = strtolower($actionElement->getAttribute("repeat"));
        if ( "true" == $this->repeat || "yes" == $this->repeat ) {
            $this->repeat = $this->dueDate;
        }
        $this->transitionName = $actionElement->getAttribute("transition");
    }
    
    //   public void execute(ExecutionContext executionContext) throws Exception {
    //     Timer timer = createTimer(executionContext);
    //     SchedulerService schedulerService = (SchedulerService) Services.getCurrentService(Services.SERVICENAME_SCHEDULER);
    //     schedulerService.createTimer(timer);
    //   }
    

    //   protected Timer createTimer(ExecutionContext executionContext) {
    //     Timer timer = new Timer(executionContext.getToken());
    //     timer.setName(timerName);
    //     timer.setRepeat(repeat);
    //     if (dueDate!=null) {
    //       Duration duration = new Duration(dueDate);
    //       Date dueDateDate = businessCalendar.add(new Date(), duration);
    //       timer.setDueDate(dueDateDate);
    //     }
    //     timer.setAction(timerAction);
    //     timer.setTransitionName(transitionName);
    //     timer.setGraphElement(executionContext.getEventSource());
    //     timer.setTaskInstance(executionContext.getTaskInstance());
    

    //     // if this action was executed for a graph element
    //     if ( (getEvent()!=null)
    //          && (getEvent().getGraphElement()!=null)
    //        ) {
    //       GraphElement graphElement = getEvent().getGraphElement();
    //       try {
    //         executionContext.setTimer(timer);
    //         // fire the create timer event on the same graph element
    //         graphElement.fireEvent("timer-create", executionContext);
    //       } finally {
    //         executionContext.setTimer(null);
    //       }
    //     }
    

    //     return timer;
    //   }
    public function getDueDate() {
        return $this->dueDate;
    }

    public function setDueDate($dueDateDuration) {
        $this->dueDate = $dueDateDuration;
    }

    public function getRepeat() {
        return $this->repeat;
    }

    public function setRepeat($repeatDuration) {
        $this->repeat = $repeatDuration;
    }

    public function getTransitionName() {
        return $this->transitionName;
    }

    public function setTransitionName($transitionName) {
        $this->transitionName = $transitionName;
    }

    public function getTimerName() {
        return $this->timerName;
    }

    public function setTimerName($timerName) {
        $this->timerName = $timerName;
    }

    public function getTimerAction() {
        return $this->timerAction;
    }

    public function setTimerAction(Action $timerAction = null) {
        $this->timerAction = $this->timerAction;
    }
}