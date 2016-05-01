<?php
namespace com\coherentnetworksolutions\pbpm;

use com\coherentnetworksolutions\pbpm\db\GraphSession;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use Doctrine\ORM\EntityManager;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;
use com\coherentnetworksolutions\pbpm\graph\exe\Token;
use Doctrine\Common\Collections\ArrayCollection;

class pbpmContext {
    /**
     * @var \Logger
     */
    private $log;
    
    /**
     * @var GraphSession
     */
    private $graphSession;
    
    /**
     * @var EntityManager
     */
    private $entityManager;
    
    //@todo: upsize to object
    private $services = [];
    
    /**
     * @var ArrayCollection
     */
    private $autoSaveProcessInstance;
    
    static private $current;
    
    public function __construct(EntityManager $entityManager) {
        $this->log = \Logger::getLogger(__CLASS__);
        $this->log->debug("creating AzBbpmContext");
        $this->entityManager = $entityManager;
        $this->graphSession = new GraphSession($entityManager);
        $this->autoSaveProcessInstance = new ArrayCollection();
        self::$current = $this;
    }
    
    public static function getCurrentContext() {
//     	if (is_null(self::$current)) { 
//     		throw new \Exception("I'm not really smart enough to do this. New me first");
//     	}
		// null valid??
    	return self::$current;
    	
    }
    /**
     * @return GraphSession
     */
    public function getGraphSession() {
        return $this->graphSession;
    }

    public function close() {
        $this->log->debug("close()");
    }
    
    public function save($thingy) {
    	if ($thingy instanceof Token) {
//     		$this->ensureOpen();
    		$processInstance = $thingy->getProcessInstance();
    	} elseif($thingy instanceof ProcessInstance) {
    		$processInstance = $thingy;
    	}
    	$this->entityManager->persist($processInstance);
    	$this->entityManager->flush($processInstance);
    }
    
    // convenience methods //////////////////////////////////////////////////////
    
    /**
     * deploys a process definition. For parsing process definitions from archives, see the static
     * parseXxx methods on {@link ProcessDefinition}.
     */
    public function deployProcessDefinition(ProcessDefinition $processDefinition) {
    	$this->graphSession->deployProcessDefinition($processDefinition);
    }
    
    /**
     * creates a new process instance for the latest version of the process definition with the
     * given name.
     *
     * @throws JbpmException when no processDefinition with the given name is deployed.
     * @return ProcessInstance
     */
    public function newProcessInstance($processDefinitionName) {
    	$processDefinition = $this->getGraphSession()->findLatestProcessDefinition($processDefinitionName);
    	return new ProcessInstance($processDefinition);
    }
    
    /**
     * creates a new process instance for the latest version of the process definition
     * with the given name and registers it for auto-save.
     * @throws JbpmException when no processDefinition with the given name is deployed.
     * @return ProcessInstance
     */
    public function  newProcessInstanceForUpdate($processDefinitionName) {
    	$processDefinition = $this->getGraphSession()->findLatestProcessDefinition($processDefinitionName);
    	$processInstance = new ProcessInstance($processDefinition);
    	$this->addAutoSaveProcessInstance($processInstance);
    	return $processInstance;
    }
    
    
    private function addAutoSaveProcessInstance(ProcessInstance $processInstance) {
    	$this->autoSaveProcessInstances->add($processInstance);
    }
}