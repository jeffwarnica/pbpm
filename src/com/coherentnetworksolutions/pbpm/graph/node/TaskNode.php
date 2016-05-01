<?php

namespace com\coherentnetworksolutions\pbpm\graph\node;

use com\coherentnetworksolutions\pbpm\jpdl\xml\Parsable;
use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\Task;

/**
 * @entity
 **/
class TaskNode extends Node implements Parsable {
    
    /**
     * execution always continues, regardless wether tasks are created or still unfinished.
     */
    public static $SIGNAL_UNSYNCHRONIZED = 0;
    /**
     * execution never continues, regardless wether tasks are created or still unfinished.
     */
    public static $SIGNAL_NEVER = 1;
    /**
     * proceeds execution when the first task instance is completed.
     * when no tasks are created on entrance of this node, execution is continued.
     */
    public static $SIGNAL_FIRST = 2;
    /**
     * proceeds execution when the first task instance is completed.
     * when no tasks are created on entrance of this node, execution is continued.
     */
    public static $SIGNAL_FIRST_WAIT = 3;
    /**
     * proceeds execution when the last task instance is completed.
     * when no tasks are created on entrance of this node, execution waits in the task node till tasks are created.
     */
    public static $SIGNAL_LAST = 4;
    /**
     * proceeds execution when the last task instance is completed.
     * when no tasks are created on entrance of this node, execution waits in the task node till tasks are created.
     */
    public static $SIGNAL_LAST_WAIT = 5;
    
    public static function parseSignal($text) {
        $text = strtolower($text);
        if ("unsynchronized"=== $text) {
            return self::$SIGNAL_UNSYNCHRONIZED;
        } else if ("never"=== $text) {
            return self::$SIGNAL_NEVER;
        } else if ("first"=== $text) {
            return self::$SIGNAL_FIRST;
        } else if ("first-wait"=== $text) {
            return self::$SIGNAL_FIRST_WAIT;
        } else if ("last-wait"=== $text) {
            return self::$SIGNAL_LAST_WAIT;
        } else { // return default
            return self::$SIGNAL_LAST;
        }
    }
    
    public static function signalToString($signal) {
        if ($signal==self::SIGNAL_UNSYNCHRONIZED) {
            return "unsynchronized";
        } else if ($signal==self::SIGNAL_NEVER) {
            return "never";
        } else if ($signal==self::SIGNAL_FIRST) {
            return "first";
        } else if ($signal==self::SIGNAL_FIRST_WAIT) {
            return "first-wait";
        } else if ($signal==self::SIGNAL_LAST) {
            return "last";
        } else if ($signal==self::SIGNAL_LAST_WAIT) {
            return "last-wait";
        } else {
            return null;
        }
    }
    
    /**
     * @OneToMany(targetEntity="com\coherentnetworksolutions\pbpm\taskmgmt\def\Task",mappedBy="taskNode",cascade={"persist"})
     * @var ArrayCollection
     */
    private $tasks = null;
    private $signal = 4; //self::SIGNAL_LAST;
    
    private $createTasks = true;
    private $endTasks = false;
    
    public function __construct($name = null) {
        $this->tasks = new ArrayCollection();
        parent::__construct($name);
    }
    
    public function read(\DOMElement $element, JpdlXmlReader $jpdlReader) {
        // get the signal
        $signalText = $element->getAttribute("signal");
        if (!is_null($signalText)) {
            $this->signal = self::parseSignal($signalText);
        }
    
        // create tasks
        $createTasksText = $element->getAttribute("create-tasks");
        if ($createTasksText!="") {
            if (("no" == strtolower($createTasksText))
                            || ("false" == strtolower($createTasksText)) ) {
                                $this->createTasks = false;
                            }
        }
    
        // create tasks
        $removeTasksText = $element->getAttribute("end-tasks");
        if ($removeTasksText == "") {
            if (("yes"== strtolower($removeTasksText))
                            || ("true" == strtolower($removeTasksText)) ) {
                                $this->endTasks = true;
                            }
        }
    
        // parse the tasks
        $jpdlReader->readTasks($element, $this);
    }
    
    public function addTask(Task $task) {
        $this->tasks->set($task->getName(), $task);
        $task->setTaskNode($this);
    }
    
//     public function getTaskMgmtInstance(Token $token) {
//         return $token->getProcessInstance()->getInstance(TaskMgmtInstance.class);
//     }
    
    // getters and setters
    /////////////////////////////////////////////////////////////////////////////
    
    
    /**
     * is the task in this task-node with the given name or null if the given task
     * does not exist in this node.
     */
    public function getTask($taskName) {
        return $this->tasks->get($taskName);
    }
    
    public function getTasks() {
        return $this->tasks;
    }
    public function getSignal() {
        return $this->signal;
    }
    public function getCreateTasks() {
        return $this->createTasks;
    }
    public function isEndTasks() {
        return $this->endTasks;
    }
}