<?php

namespace com\coherentnetworksolutions\pbpm\db;

use Doctrine\ORM\EntityManager;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;

class GraphSession {
	
	/**
	 *
	 * @var \Logger
	 */
	private $log;
	
	/**
	 *
	 * @var pbpmSession
	 */
	private $pbpmSession = null;
	
	/**
	 *
	 * @var EntityManager
	 */
	private $entityManager = null;
	public function __construct($somethingSession) {
		$this->log = \Logger::getLogger(__CLASS__);
		$this->log->debug("Creating GraphSession");
		
		if ($somethingSession instanceof pbpmSession){
			$this->pbpmSession = $somethingSession;
			$this->entityManager = $this->pbpmSession->getEntityManager();
		}elseif ($somethingSession instanceof EntityManager){
			$this->entityManager = $somethingSession;
			$this->pbpmSession = new pbpmSession($this->entityManager);
		}else{
			$this->log->debug("Throwing something");
			throw new \Exception("GraphSession::_construct() fail. Bad compliler not being polymorphic");
		}
	}
	
	// // process definitions //////////////////////////////////////////////////////
	public function deployProcessDefinition(ProcessDefinition $processDefinition) {
		$processDefinitionName = $processDefinition->getName();
		// if the process definition has a name (process versioning only applies to named process definitions)
		if ($processDefinitionName != ""){
			// find the current latest process definition
			$previousLatestVersion = $this->findLatestProcessDefinition($processDefinitionName);
			// if there is a current latest process definition
			if (!is_null($previousLatestVersion)){
				// take the next version number
				$processDefinition->setVersion($previousLatestVersion->getVersion() + 1);
			}else{
				// start from 1
				$processDefinition->setVersion(1);
			}
			
			$this->entityManager->persist($processDefinition);
			$this->entityManager->flush();
		}else{
			throw new \Exception("process definition does not have a name");
		}
	}
	
	/**
	 * saves the process definitions.
	 * this method does not assign a version
	 * number. that is the responsibility of the {@link org.jbpm.jpdl.par.ProcessArchiveDeployer}.
	 */
	public function saveProcessDefinition(ProcessDefinition $processDefinition) {
		try{
			$this->log->debug("About to persist(\$processDefinition);. It thinks it's id is: {$processDefinition->getId()}");
			$this->entityManager->persist($processDefinition);
			$this->entityManager->flush();
			$this->log->debug("After persist(\$processDefinition);. It thinks it's id is: {$processDefinition->getId()}");
		}catch ( \Exception $e ){
			// print $e->getTraceAsString();
			$this->log->error($e);
			$this->pbpmSession->handleException();
			throw new \Exception("couldn't save process definition '{$processDefinition->getId()}'", null, $e);
		}
	}
	
	/**
	 * loads a process definition from the database by the identifier.
	 *
	 * @throws a JbpmException in case the referenced process definition doesn't exist.
	 * @return ProcessDefinition
	 */
	public function loadProcessDefinition($processDefinitionId) {
		try{
			$obj = $this->entityManager->find("com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition", $processDefinitionId);
			$this->log->debug("Got an obj back (class():" . get_class($obj));
			return $obj;
		}catch ( Exception $e ){
			print $e->getTraceAsString();
			$this->log->error("loadProcessDefinition([$processDefinitionId]) error: [{$e}]");
			// jbpmSession.handleException();
			throw new \Exception("couldn't load process definition {$processDefinitionId}", null, $e);
		}
	}
	
	/**
	 * saves the process definitions.
	 * this method does not assign a version
	 * number. that is the responsibility of the {@link org.jbpm.jpdl.par.ProcessArchiveDeployer}.
	 */
	public function saveProcessInstance(ProcessInstance $processInstance) {
		try{
			$this->log->debug("About to persist(\$processInstance);. It thinks it's id is: {$processInstance->getId()}");
			$this->entityManager->persist($processInstance);
			$this->entityManager->flush();
			$this->log->debug("After persist(\$processInstance);. It thinks it's id is: {$processInstance->getId()}");
		}catch ( \Exception $e ){
			// print $e->getTraceAsString();
			$this->log->error($e);
			$this->pbpmSession->handleException();
			throw new \Exception("couldn't save process instance '{$processInstance->getId()}'", null, $e);
		}
	}
	
	/**
	 * loads a process instance from the database by the identifier.
	 *
	 * @throws a JbpmException in case the referenced process definition doesn't exist.
	 * @return ProcessInstance
	 */
	public function loadProcessInstance($processInstanceId) {
		try{
			$obj = $this->entityManager->find('com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance', $processInstanceId);
			$this->log->debug("Got an obj back (class():" . get_class($obj));
			return $obj;
		}catch ( Exception $e ){
			print $e->getTraceAsString();
			$this->log->error("loadProcessDefinition([$processInstanceId]) error: [{$e}]");
			throw new \Exception("couldn't load process instance {$processInstanceId}", null, $e);
		}
	}
	
	// /**
	// * gets a process definition from the database by the identifier.
	// * @return the referenced process definition or null in case it doesn't exist.
	// */
	// public ProcessDefinition getProcessDefinition(long processDefinitionId) {
	// try {
	// return (ProcessDefinition) session.get( ProcessDefinition.class, new Long(processDefinitionId) );
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't get process definition '" + processDefinitionId + "'", e);
	// }
	// }
	
	// /**
	// * queries the database for a process definition with the given name and version.
	// */
	// public ProcessDefinition findProcessDefinition(String name, int version) {
	// ProcessDefinition processDefinition = null;
	// try {
	// Query query = session.getNamedQuery("GraphSession.findProcessDefinitionByNameAndVersion");
	// query.setString("name", name);
	// query.setInteger("version", version);
	// processDefinition = (ProcessDefinition) query.uniqueResult();
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't get process definition with name '"+name+"' and version '"+version+"'", e);
	// }
	// return processDefinition;
	// }
	
	/**
	 * queries the database for the latest version of a process definition with the given name.
	 * 
	 * @return ProcessDefinition
	 */
	public function findLatestProcessDefinition($name) {
		$this->log->debug(">findLatestProcessDefinition([$name])");
		try{
			
			$query = $this->entityManager->createQuery('SELECT pd FROM com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition pd WHERE pd.name= :name ORDER BY pd.		version DESC');
			$query->setParameter('name', $name);
			$this->log->debug("\t QUERY IS: {$query->getSQL()}\n");
			$pd = $query->getResult();
			if (sizeof($pd) == 1){
				$this->log->debug("Got an obj back (class():" . get_class($pd[0]));
				return $pd[0];
			}else{
				return null;
			}
		}catch ( Exception $e ){
			print $e->getTraceAsString();
			$this->log->error("findLatestProcessDefinition([$name]) error: [{$e}]");
			// jbpmSession.handleException();
			throw new \Exception("couldn't load latest processDefinition {$name}", null, $e);
		}
		
		// ProcessDefinition processDefinition = null;
		// try {
		// Query query = session.getNamedQuery("GraphSession.findLatestProcessDefinitionQuery");
		// query.setString("name", name);
		// query.setMaxResults(1);
		// processDefinition = (ProcessDefinition) query.uniqueResult();
		// } catch (Exception e) {
		// e.printStackTrace(); log.error(e);
		// jbpmSession.handleException();
		// throw new JbpmException("couldn't find process definition '" + name + "'", e);
		// }
		// return processDefinition;
	}
	
	// /**
	// * queries the database for the latest version of each process definition.
	// * Process definitions are distinct by name.
	// */
	// public List findLatestProcessDefinitions() {
	// List processDefinitions = new ArrayList();
	// Map processDefinitionsByName = new HashMap();
	// try {
	// Query query = session.getNamedQuery("GraphSession.findAllProcessDefinitions");
	// Iterator iter = query.list().iterator();
	// while (iter.hasNext()) {
	// ProcessDefinition processDefinition = (ProcessDefinition) iter.next();
	// String processDefinitionName = processDefinition.getName();
	// ProcessDefinition previous = (ProcessDefinition) processDefinitionsByName.get(processDefinitionName);
	// if ( (previous==null)
	// || (previous.getVersion()<processDefinition.getVersion())
	// ){
	// processDefinitionsByName.put(processDefinitionName, processDefinition);
	// }
	// }
	// processDefinitions = new ArrayList(processDefinitionsByName.values());
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't find latest versions of process definitions", e);
	// }
	// return processDefinitions;
	// }
	
	// /**
	// * queries the database for all process definitions, ordered by name (ascending), then by version (descending).
	// */
	// public List findAllProcessDefinitions() {
	// try {
	// Query query = session.getNamedQuery("GraphSession.findAllProcessDefinitions");
	// return query.list();
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't find all process definitions", e);
	// }
	// }
	
	// /**
	// * queries the database for all versions of process definitions with the given name, ordered by version (descending).
	// */
	// public List findAllProcessDefinitionVersions(String name) {
	// try {
	// Query query = session.getNamedQuery("GraphSession.findAllProcessDefinitionVersions");
	// query.setString("name", name);
	// return query.list();
	// } catch (HibernateException e) {
	// e.printStackTrace(); log.error(e);
	// throw new JbpmException("couldn't find all versions of process definition '"+name+"'", e);
	// }
	// }
	
	// public void deleteProcessDefinition(long processDefinitionId) {
	// deleteProcessDefinition(loadProcessDefinition(processDefinitionId));
	// }
	
	// public void deleteProcessDefinition(ProcessDefinition processDefinition) {
	// if (processDefinition==null) throw new JbpmException("processDefinition is null in JbpmSession.deleteProcessDefinition()");
	// try {
	// // delete all the process instances of this definition
	// List processInstances = findProcessInstances(processDefinition.getId());
	// if (processInstances!=null) {
	// Iterator iter = processInstances.iterator();
	// while (iter.hasNext()) {
	// deleteProcessInstance((ProcessInstance) iter.next());
	// }
	// }
	
	// // then delete the process definition
	// session.delete(processDefinition);
	
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't delete process definition '" + processDefinition.getId() + "'", e);
	// }
	// }
	
	// // process instances ////////////////////////////////////////////////////////
	
	// /**
	// * @deprecated use {@link org.jbpm.JbpmContext#save(ProcessInstance)} instead.
	// * @throws UnsupportedOperationException
	// */
	// public void saveProcessInstance(ProcessInstance processInstance) {
	// throw new UnsupportedOperationException("use JbpmContext.save(ProcessInstance) instead");
	// }
	
	// /**
	// * loads a process instance from the database by the identifier.
	// * This throws an exception in case the process instance doesn't exist.
	// * @see #getProcessInstance(long)
	// * @throws a JbpmException in case the process instance doesn't exist.
	// */
	// public ProcessInstance loadProcessInstance(long processInstanceId) {
	// try {
	// ProcessInstance processInstance = (ProcessInstance) session.load( ProcessInstance.class, new Long(processInstanceId) );
	// return processInstance;
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't load process instance '" + processInstanceId + "'", e);
	// }
	// }
	
	// /**
	// * gets a process instance from the database by the identifier.
	// * This method returns null in case the given process instance doesn't exist.
	// */
	// public ProcessInstance getProcessInstance(long processInstanceId) {
	// try {
	// ProcessInstance processInstance = (ProcessInstance) session.get( ProcessInstance.class, new Long(processInstanceId) );
	// return processInstance;
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't get process instance '" + processInstanceId + "'", e);
	// }
	// }
	
	// /**
	// * loads a token from the database by the identifier.
	// * @return the token.
	// * @throws JbpmException in case the referenced token doesn't exist.
	// */
	// public Token loadToken(long tokenId) {
	// try {
	// Token token = (Token) session.load(Token.class, new Long(tokenId));
	// return token;
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't load token '" + tokenId + "'", e);
	// }
	// }
	
	// /**
	// * gets a token from the database by the identifier.
	// * @return the token or null in case the token doesn't exist.
	// */
	// public Token getToken(long tokenId) {
	// try {
	// Token token = (Token) session.get(Token.class, new Long(tokenId));
	// return token;
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't get token '" + tokenId + "'", e);
	// }
	// }
	
	// /**
	// * locks a process instance in the database.
	// */
	// public void lockProcessInstance(long processInstanceId) {
	// lockProcessInstance(loadProcessInstance(processInstanceId));
	// }
	
	// /**
	// * locks a process instance in the database.
	// */
	// public void lockProcessInstance(ProcessInstance processInstance) {
	// try {
	// session.lock( processInstance, LockMode.UPGRADE );
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't lock process instance '" + processInstance.getId() + "'", e);
	// }
	// }
	
	// /**
	// * fetches all processInstances for the given process definition from the database.
	// * The returned list of process instances is sorted start date, youngest first.
	// */
	// public List findProcessInstances(long processDefinitionId) {
	// List processInstances = null;
	// try {
	// Query query = session.getNamedQuery("GraphSession.findAllProcessInstancesForADefinition");
	// query.setLong("processDefinitionId", processDefinitionId);
	// processInstances = query.list();
	
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't load process instances for process definition '" + processDefinitionId + "'", e);
	// }
	// return processInstances;
	// }
	
	// public void deleteProcessInstance(long processInstanceId) {
	// deleteProcessInstance(loadProcessInstance(processInstanceId));
	// }
	
	// public void deleteProcessInstance(ProcessInstance processInstance) {
	// deleteProcessInstance(processInstance, true, true, true);
	// }
	
	// public void deleteProcessInstance(ProcessInstance processInstance, boolean includeTasks, boolean includeTimers, boolean includeMessages) {
	// if (processInstance==null) throw new JbpmException("processInstance is null in JbpmSession.deleteProcessInstance()");
	// try {
	// // find the tokens
	// Query query = session.getNamedQuery("GraphSession.findTokensForProcessInstance");
	// query.setEntity("processInstance", processInstance);
	// List tokens = query.list();
	
	// // deleteSubProcesses
	// Iterator iter = tokens.iterator();
	// while (iter.hasNext()) {
	// Token token = (Token) iter.next();
	// deleteSubProcesses(token);
	
	// // messages
	// if (includeMessages) {
	// query = session.getNamedQuery("GraphSession.deleteMessagesForToken");
	// query.setEntity("token", token);
	// query.executeUpdate();
	// }
	// }
	
	// // tasks
	// if (includeTasks) {
	// query = session.getNamedQuery("GraphSession.findTaskInstanceIdsForProcessInstance");
	// query.setEntity("processInstance", processInstance);
	// List taskInstanceIds = query.list();
	
	// query = session.getNamedQuery("GraphSession.deleteTaskInstancesById");
	// query.setParameterList("taskInstanceIds", taskInstanceIds);
	// }
	
	// // timers
	// if (includeTimers) {
	// query = session.getNamedQuery("SchedulerSession.deleteTimersForProcessInstance");
	// query.setEntity("processInstance", processInstance);
	// query.executeUpdate();
	// }
	
	// // delete the logs for all the process instance's tokens
	// if ( (tokens!=null)
	// && (!tokens.isEmpty())
	// ) {
	// query = session.getNamedQuery("GraphSession.selectLogsForTokens");
	// query.setParameterList("tokens", tokens);
	// List logs = query.list();
	// iter = logs.iterator();
	// while (iter.hasNext()) {
	// session.delete(iter.next());
	// }
	// }
	
	// // then delete the process instance
	// session.delete(processInstance);
	
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't delete process instance '" + processInstance.getId() + "'", e);
	// }
	// }
	
	// void deleteSubProcesses(Token token) {
	// ProcessInstance subProcessInstance = token.getSubProcessInstance();
	// if (subProcessInstance!=null){
	// subProcessInstance.setSuperProcessToken(null);
	// token.setSubProcessInstance(null);
	// deleteProcessInstance(subProcessInstance);
	// }
	// if (token.getChildren()!=null) {
	// Iterator iter = token.getChildren().values().iterator();
	// while (iter.hasNext()) {
	// Token child = (Token) iter.next();
	// deleteSubProcesses(child);
	// }
	// }
	// }
	
	// public List findActiveNodesByProcessInstance(ProcessInstance processInstance) {
	// List results = null;
	// try {
	// Query query = session.getNamedQuery("GraphSession.findActiveNodesByProcessInstance");
	// query.setEntity("processInstance", processInstance);
	// results = query.list();
	
	// } catch (Exception e) {
	// e.printStackTrace(); log.error(e);
	// jbpmSession.handleException();
	// throw new JbpmException("couldn't active nodes for process instance '" + processInstance + "'", e);
	// }
	// return results;
	// }
	
	// private static final Log log = LogFactory.getLog(GraphSession.class);
}