<?php
namespace com\coherentnetworksolutions\pbpm\taskmgmt\def;
use com\coherentnetworksolutions\pbpm\context\def\VariableAccess;
use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\instantiation\Delegation;

/**
 * is a controller for one task. this object either delegates to a custom
 * {@link org.jbpm.taskmgmt.def.TaskControllerHandler} or it is configured
 * with {@link org.jbpm.context.def.VariableAccess}s to perform the default
 * behaviour of the controller functionality for a task.
 */
/** @entity **/
class TaskController {
    
    /**
     * @Id @Column(type="integer")
     * @var int
     * */
    public $id;

    /**
     * allows the user to specify a custom task controller handler. if this is
     * specified, the other member variableInstances are ignored. so either a
     * taskControllerDelegation is specified or the variable- and signalMappings
     * are specified, but not both.
     * @OneToOne(targetEntity="com\coherentnetworksolutions\pbpm\instantiation\Delegation")
     * @var Delegation
     */
     
    private $taskControllerDelegation = null;

    /**
     * maps process variable names (java.lang.String) to VariableAccess objects.
     * @ManyToOne(targetEntity="com\coherentnetworksolutions\pbpm\context\def\VariableAccess")
     * @var ArrayCollection
     */
    private $variableAccesses;

    public function __construct() {
        $this->variableAccesses = new ArrayCollection(); 
    }

//     /**
//      * extract the list of information from the process variables and make them available locally.
//      * Note that if no task instance variables are specified, the full process variables scope will be
//      * visible (that means that the user did not specify a special task instance scope).
//      */
//     public void initializeVariables(TaskInstance taskInstance) {
//         if (taskControllerDelegation != null) {
//             TaskControllerHandler taskControllerHandler = (TaskControllerHandler) taskControllerDelegation.instantiate();
//             ProcessInstance processInstance = taskInstance.getTaskMgmtInstance().getProcessInstance();
//             ContextInstance contextInstance = (processInstance!=null ? processInstance.getContextInstance() : null);
//             Token token = taskInstance.getToken();
//             taskControllerHandler.initializeTaskVariables(taskInstance, contextInstance, token);

//         } else {
//             Token token = taskInstance.getToken();
//             ProcessInstance processInstance = token.getProcessInstance();
//             ContextInstance contextInstance = processInstance.getContextInstance();

//             if (variableAccesses!=null) {
//                 Iterator iter = variableAccesses.iterator();
//                 while (iter.hasNext()) {
//                     VariableAccess variableAccess = (VariableAccess) iter.next();
//                     String mappedName = variableAccess.getMappedName();
//                     if (variableAccess.isReadable()) {
//                         String variableName = variableAccess.getVariableName();
//                         Object value = contextInstance.getVariable(variableName, token);
//                         log.debug("creating task instance variable '"+mappedName+"' from process variable '"+variableName+"', value '"+value+"'");
//                         taskInstance.setVariableLocally(mappedName, value);
//                     } else {
//                         log.debug("creating task instance local variable '"+mappedName+"'. initializing with null value.");
//                         taskInstance.setVariableLocally(mappedName, null);
//                     }
//                 }
//             }
//         }
//     }

//     /**
//      * update the process variables from the the task-instance variables.
//      */
//     public void submitParameters(TaskInstance taskInstance) {
//         if (taskControllerDelegation != null) {
//             TaskControllerHandler taskControllerHandler = (TaskControllerHandler) taskControllerDelegation.instantiate();
//             ProcessInstance processInstance = taskInstance.getTaskMgmtInstance().getProcessInstance();
//             ContextInstance contextInstance = (processInstance!=null ? processInstance.getContextInstance() : null);
//             Token token = taskInstance.getToken();
//             taskControllerHandler.submitTaskVariables(taskInstance, contextInstance, token);

//         } else {

//             Token token = taskInstance.getToken();
//             ProcessInstance processInstance = token.getProcessInstance();
//             ContextInstance contextInstance = processInstance.getContextInstance();

//             if (variableAccesses!=null) {
//                 String missingTaskVariables = null;
//                 Iterator iter = variableAccesses.iterator();
//                 while (iter.hasNext()) {
//                     VariableAccess variableAccess = (VariableAccess) iter.next();
//                     String mappedName = variableAccess.getMappedName();
//                     // first check if the required variableInstances are present
//                     if ( (variableAccess.isRequired())
//                                     && (! taskInstance.hasVariableLocally(mappedName))
//                     ) {
//                         if (missingTaskVariables==null) {
//                             missingTaskVariables = mappedName;
//                         } else {
//                             missingTaskVariables += ", "+mappedName;
//                         }
//                     }
//                 }

//                 // if there are missing, required parameters, throw an IllegalArgumentException
//                 if (missingTaskVariables!=null) {
//                     throw new IllegalArgumentException("missing task variables: "+missingTaskVariables);
//                 }

//                 iter = variableAccesses.iterator();
//                 while (iter.hasNext()) {
//                     VariableAccess variableAccess = (VariableAccess) iter.next();
//                     String mappedName = variableAccess.getMappedName();
//                     String variableName = variableAccess.getVariableName();
//                     if (variableAccess.isWritable()) {
//                         Object value = taskInstance.getVariable(mappedName);
//                         if (value!=null) {
//                             log.debug("submitting task variable '"+mappedName+"' to process variable '"+variableName+"', value '"+value+"'");
//                             contextInstance.setVariable(variableName, value, token);
//                         }
//                     }
//                 }
//             }
//         }
//     }


//     // getters and setters //////////////////////////////////////////////////////

    public function getVariableAccesses() {
        return $this->variableAccesses;
    }
    
    /**
     * @return Delegation
     */
    public function getTaskControllerDelegation() {
        return $this->taskControllerDelegation;
    }
    public function setTaskControllerDelegation(Delegation $taskControllerDelegation) {
        $this->taskControllerDelegation = $taskControllerDelegation;
    }

    public function setVariableAccesses($variableAccesses = array()) {
        foreach ($variableAccesses as $_) {
            $this->variableAccesses->add($_);
        }
    }

}