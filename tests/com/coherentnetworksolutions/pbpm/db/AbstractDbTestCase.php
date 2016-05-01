<?php
namespace com\coherentnetworksolutions\pbpm\db;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use com\coherentnetworksolutions\pbpm\pbpmContext;

abstract class AbstractDbTestCase extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Logger
     */
    protected $log;

    /**
     * @var GraphSession
     */
    protected $graphSession;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var pbpmContext
     */
    protected $pbpmContext;

    private $schemaTool;
    
    private $processDefinitionIds = array();
    
    /**
     * @before
     */
    public function setUp() {
        
        $this->log = \Logger::getLogger(__CLASS__);
        $this->log->debug("### starting " .  $this->getName() . " ####################################################");
        
        $this->createSchema();
        $this->log->debug("### after createSchema() ####################################################");
        $this->initializeMembers();
        
        $this->log->debug("### ##################### finished setUp()");
    }

    /**
     * @after
     */
    protected function tearDown() {
//     	print "Starting tearDown for {$this->getName()}\n";
        $this->dropSchema();
        $this->resetMembers();
        $this->log->debug("### " . $this->getName() . " done ####################################################");
    }

    /**
     * @param $thing
     */
    protected function saveAndReload($thing) {
    	if ($thing instanceof ProcessDefinition) {
	        $this->graphSession->saveProcessDefinition($thing);
	        return $this->graphSession->loadProcessDefinition($thing->getId());
    	}elseif ($thing instanceof ProcessInstance) {
    		$this->graphSession->saveProcessInstance($thing);
    		return $this->graphSession->loadProcessInstance($thing->getId());
    	} else {
    		throw new \Exception("I don't know how to saveAndReload a " . get_class($thing));
    	}
    }

    
//     protected ProcessInstance saveAndReload(ProcessInstance pi) {
//     	jbpmContext.save(pi);
//     	newTransaction();
//     	return graphSession.loadProcessInstance(pi.getId());
//     }
    
//     protected TaskInstance saveAndReload(TaskInstance taskInstance) {
//     	jbpmContext.save(taskInstance);
//     	newTransaction();
//     	return (TaskInstance) session.load(TaskInstance.class, new Long(taskInstance.getId()));
//     }
    
//     protected ProcessDefinition saveAndReload(ProcessDefinition pd) {
//     	graphSession.saveProcessDefinition(pd);
//     	registerForDeletion(pd);
//     	return graphSession.loadProcessDefinition(pd.getId());
//     }
    
//     protected ProcessLog saveAndReload(ProcessLog processLog) {
//     	loggingSession.saveProcessLog(processLog);
//     	newTransaction();
//     	return loggingSession.loadProcessLog(processLog.getId());
//     }
    
    
    
    private function createSchema() {
        
        //Well, fuck me in the ass with a stick.
        global $entityManager;
        $this->entityManager = $entityManager;
        
        $this->schemaTool = new SchemaTool($this->entityManager);
        
        $this->entityManager->clear();
        
        $classes = $this->entityManager->getMetaDataFactory()->getAllMetaData();
        $this->schemaTool->dropSchema($classes);
        $this->schemaTool->createSchema($classes);
    }

    private function initializeMembers() {
        $this->log->debug("start of initializeMembers()");
        $this->pbpmContext = new pbpmContext($this->entityManager);
        $this->graphSession = $this->pbpmContext->getGraphSession();
        $this->log->debug("end   of initializeMembers()");
        //         taskMgmtSession = jbpmContext.getTaskMgmtSession();
        //         loggingSession = jbpmContext.getLoggingSession();
        //         schedulerSession = jbpmContext.getSchedulerSession();
        //         contextSession = jbpmContext.getContextSession();
        //         messagingSession = jbpmContext.getMessagingSession();
    }

    protected function resetMembers() {
        $this->eneityManager = null;
        $this->graphSession = null;
        $this->taskMgmtSession = null;
        $this->loggingSession = null;
        $this->schedulerSession = null;
        $this->contextSession = null;
        $this->messagingSession = null;
    }
    
    protected function dropSchema() {
    	try {
	        $classes = $this->entityManager->getMetaDataFactory()->getAllMetaData();
	        $this->schemaTool->dropSchema($classes);
	        $this->schemaTool->dropDatabase();
    	} catch (\Exception $e) {
    		print $e->__toString();
    	}
    }
    
    protected function deployProcessDefinition(ProcessDefinition $processDefinition) {
    	$this->pbpmContext->deployProcessDefinition($processDefinition);
//     	$this->registerForDeletion($processDefinition);
    }
    
//     private function registerForDeletion(ProcessDefinition $processDefinition) {
//     	// start new transaction to avoid registering an uncommitted process definition
//     	$this->newTransaction();
//     	$this->processDefinitionIds[] = $processDefinition->getId();
//     }
    
//     protected function newTransaction() {
//     	$this->closePbpmContext();
//     	$this->createPbpmContext();
//     }
    
//     protected function createJbpmContext() {
//     	jbpmContext = getJbpmConfiguration().createJbpmContext();
//     	initializeMembers();
//     }
    
//     protected void closeJbpmContext() {
//     	if (jbpmContext != null) {
//     		resetMembers();
    
//     		jbpmContext.close();
//     		jbpmContext = null;
//     	}
//     }
    
}