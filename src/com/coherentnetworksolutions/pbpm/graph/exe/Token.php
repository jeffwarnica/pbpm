<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\pBpmException;

/**
 * represents one path of execution and maintains a pointer to a node in the
 * {@link org.jbpm.graph.def.ProcessDefinition}.
 * Most common way to get a hold of the token
 * objects is with {@link ProcessInstance#getRootToken()} or
 * {@link org.jbpm.graph.exe.ProcessInstance#findToken(String)}.
 * @entity
 */
class Token {
	
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * @Column(type="integer")
	 *
	 * @var int
	 */
	protected $version = -1;
	
	/**
	 * @Column(type="string", nullable=true)
	 *
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="datetime", nullable=true)
	 *
	 * @var \DateTime
	 */
	protected $start;
	/**
	 * @Column(type="datetime", nullable=true)
	 *
	 * @var \DateTime
	 */
	protected $end;
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\def\Node",cascade={"persist"})
	 *
	 * @var Node
	 */
	protected $node;
	
	/**
	 * @Column(type="datetime", nullable=true)
	 *
	 * @var \DateTime
	 */
	protected $nodeEnter;
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance",cascade={"persist"})
	 *
	 * @var ProcessInstance
	 */
	protected $processInstance;
	
	/**
	 * @ManyToOne(targetEntity="Token", inversedBy="children")
	 * @JoinColumn(name="p_id", referencedColumnName="id")
	 *
	 * @var ProcessInstance
	 */
	protected $parent;
	
	/**
	 * @OneToMany(targetEntity="Token", mappedBy="parent",cascade={"persist"})
	 *
	 * @var ArrayCollection
	 */
	protected $children;
	
	/**
	 * @OneToMany(targetEntity="Comment", mappedBy="parent",cascade={"persist"})
	 *
	 * @var ArrayCollection $comments
	 */
	protected $comments;
	protected $subProcessInstance;
	/**
	 * @Column(type="integer")
	 *
	 * @var integer $nextLogIndex
	 */
	protected $nextLogIndex = 0;
	
	/**
	 * @Column(type="boolean")
	 */
	private $isAbleToReactivateParent = true;
	/**
	 * @Column(type="boolean")
	 */
	private $isTerminationImplicit = false;
	
	/**
	 * @Column(type="boolean")
	 */
	private $isSuspended = false;
	
	/**
	 * @Column(type="string")
	 */
	private $lock = "";
	
	/**
	 * @var \Logger
	 */
	protected $log;
	
	/**
	 * creates a root token.
	 */
	public function __construct($pi_or_token = null, $name = null) {
		$this->children = new ArrayCollection();
		$this->log = \Logger::getLogger(__CLASS__);
		
		$this->start = new \DateTime();
		$this->name = $name;
		if (!is_null($pi_or_token)) {
			if ($pi_or_token instanceof ProcessInstance) {
				/**@var ProcessInstance **/
				$processInstance = $pi_or_token;
				$this->processInstance = $processInstance;
				$this->node = $processInstance->getProcessDefinition()->getStartState();
				$this->isTerminationImplicit = $processInstance->getProcessDefinition()->isTerminationImplicit();
			} elseif ($pi_or_token instanceof Token) {
				/**@var Token **/
				$token = $pi_or_token;
				$this->processInstance = $token->getProcessInstance();
				$this->node = $token->getNode();
				$this->parent = $token;
				$this->parent->addChild($this);
				$this->isTerminationImplicit = $this->parent->isTerminationImplicit();
				$this->parent->addLog("Token Created");
			}
		}
		$this->log->debug("XXX Newed up a token named:[{$name}]");
		$this->log->debug("XXX\tMy parent is:" . (is_null($this->parent) ? "NULL" : "NOT NULL"));
		// assign an id to this token before events get fired
		// skip, process instance is saved shortly after constructing root token
		// Services.assignId(this);
	}
	
	// operations
	// ///////////////////////////////////////////////////////////////////////////
	private function addChild(Token $token) {
		$this->children->set($token->getName(), $token);
	}
	
	/**
	 * sends a signal to this token.
	 * leaves the current {@link #getNode() node} over the default
	 * transition.
	 */
	public function signal($transitionNameOrObjOrCtx = null) {
		if (is_null($transitionNameOrObjOrCtx)) {
			if (is_null($this->node)) {
				throw new \Exception("[{$this}] is not positioned in a node");
			}
			
			$defaultTransition = $this->node->getDefaultLeavingTransition();
			if (is_null($defaultTransition)) {
				throw new \Exception("[{$this->node}] has no default transition");
			}
			/**@var Transition **/
			$transition = $defaultTransition;
			$executionContext = new ExecutionContext($this);
			// signal(defaultTransition, new ExecutionContext(this));
		} elseif ($transitionNameOrObjOrCtx instanceof Transition) {
			/**@var Transition **/
			$transition = $transitionNameOrObjOrCtx;
			$executionContext = new ExecutionContext($this);
		} elseif ($transitionNameOrObjOrCtx instanceof ExecutionContext) {
			$transition = $this->node->getDefaultLeavingTransition();
			$executionContext = $transitionNameOrObjOrCtx;
		} elseif (is_string($transitionNameOrObjOrCtx)) {
			$transition = $this->node->getLeavingTransition($transitionNameOrObjOrCtx );
			$executionContext = new ExecutionContext($this);
		}else {
			throw new \Exception("I'm only so poly(morphic). Try with a string, Transition, or ExecutionContext");
		}
		
		if ($this->isSuspended) {
			throw new \Exception("token is suspended");
		}
		if ($this->isLocked()) {
			throw new \Exception("token is locked by [{$this->lock}]");
		}
		if ($this->hasEnded()) {
			throw new \Exception("token has ended");
		}
		
		$this->log->debug("EXEC Signal Transition: [{$transition}]");
		
		// startCompositeLog(new SignalLog(transition));
		try {
			// fire the event before-signal
			$signalNode = $this->node;
			$signalNode->fireEvent(Event::EVENTTYPE_BEFORE_SIGNAL, $executionContext);
			
			// start calculating the next state
			$this->node->leave($executionContext, $transition);
			
			// if required, check if this token is implicitly terminated
			$this->checkImplicitTermination();
			
			// fire the event after-signal
			$signalNode->fireEvent(Event::EVENTTYPE_AFTER_SIGNAL, $executionContext);
		} finally {
			// endCompositeLog();
		}
	}
	
	/**
	* a set of all the leaving transitions on the current node for which the condition expression
	* resolves to true.
	* @return ArrayCollection
	*/
	public function getAvailableTransitions() {
		$availableTransitions =new ArrayCollection();
		if (is_null($this->node)) return $availableTransitions;
	
		$this->addAvailableTransitionsOfNode($this->node, $availableTransitions);
		return $availableTransitions;
	}
	
	/**
	* adds available transitions of that node to the Set and after that calls itself recursively
	* for the SuperSate of the Node if it has a super state
	*/
	private function addAvailableTransitionsOfNode(Node $currentNode, ArrayCollection $availableTransitions) {
		$leavingTransitions = $currentNode->getLeavingTransitions();
		if (sizeof($leavingTransitions) > 0) {
			foreach ($leavingTransitions as $transition) {
// 			for ($iter = $leavingTransitions->getIterator(); $iter->hasNext();) {
				/** @var $transition Trastion **/
				$conditionExpression = $transition->getCondition();
				if ($conditionExpression <> "") {
					$result = JbpmExpressionEvaluator::evaluate($conditionExpression, new ExecutionContext($this), "Boolean.class");
					if ($result) {
						$availableTransitions->add($transition);
					}
				}else {
					$$availableTransitions->add($transition);
				}
			}
		}
		
		if (!is_null($currentNode->getSuperState())) {
			$this->addAvailableTransitionsOfNode($currentNode->getSuperState(), $availableTransitions);
		}
	}
	
	/**
	 * ends this token with optional parent ending verification.
	 *
	 * if true (or ommitted), ends this token and all of its children (if any). this is the last active (i.e. not-ended)
	 * child of a parent token, the parent token will be ended as well and that verification will
	 * continue to propagate.
	 *
	 * @param
	 *        	verifyParentTermination specifies if the parent token should be checked for
	 *        	termination. if verifyParentTermination is set to true and this is the last non-ended child
	 *        	of a parent token, the parent token will be ended as well and the verification will
	 *        	continue to propagate.
	 */
	public function end($verifyParentTermination = true) {
		$e = "NULL";
		if (!is_null($this->end)) {
			$e = $this->end->format('Y-m-d H:i:s');
		}
		$this->log->debug("XXX end [{$this}]. ->end is [{$e}]");
		
		// if already ended, do nothing
		if (!is_null($this->end)) {
			if (!is_null($this->parent)) {
				$this->log->warn("XXX [{$this}] has ended already");
			}
			return;
		}
		$this->log->debug("XXX end, past the is_null");
		// record the end date
		// the end date also indicates that this token has ended
		$this->end = new \DateTime();
		
		// ended tokens cannot reactivate parents
		$this->isAbleToReactivateParent = false;
		
		// end all this token's children
		if (sizeof($this->children) > 0) {
			$iter = $this->children->getIterator();
			while ( $iter->valid() ) {
				$child = $iter->current();
				if (!$child->hasEnded()) {
					$this->log->debug("XXX think I have a child to end");
					$child->end();
				}
				$iter->next();
			}
		}
		
		// end the subprocess instance, if any
		if (!is_null($this->subProcessInstance)) {
			$this->log->debug("XXX think I have a subProcess to end");
			$this->subProcessInstance->end();
		}
		
		// only log child-token ends
		// process instance logs replace root token logs
		if (!is_null($this->parent)) {
			// $this->parent->addLog(new TokenEndLog($this));
		}
		
		// if there are tasks associated to this token,
		// remove signaling capabilities
		$taskMgmtInstance = $this->processInstance->getTaskMgmtInstance();
		if (!is_null($taskMgmtInstance)) {
			$taskMgmtInstance->removeSignalling($this);
		}
		
		if ($verifyParentTermination) {
			// if this is the last active token of the parent,
			// the parent needs to be ended as well
			$this->notifyParentOfTokenEnd();
		}
	}
	
	// // comments /////////////////////////////////////////////////////////////////
	
	public function addComment($commentobjOrString) {
		if (is_string($commentobjOrString)) {
			$commentobjOrString = new Comment( $commentobjOrString);
		}
		$this->comments->add($commentobjOrString);
		$commentobjOrString->setToken($this);
	}
	
	/**
	 * @return ArrayCollection
	 */
	public function getComments() {
		$this->comments;
	}
	
	// // operations helper methods ////////////////////////////////////////////////
	
	/**
	 * notifies a parent that one of its nodeMap has ended.
	 */
	private function notifyParentOfTokenEnd() {
		if ($this->isRoot()) {
			$this->processInstance->end();
		} else if (!is_null($this->parent) && !$this->parent->hasActiveChildren()) {
			$this->parent->end();
		}
	}
	
	// /**
	// * tells if this token has child tokens that have not yet ended.
	// */
	// public boolean hasActiveChildren() {
	// // try and find at least one child token that is still active (not ended)
	// if (children != null) {
	// for (Iterator iter = children.values().iterator(); iter.hasNext();) {
	// Token child = (Token) iter.next();
	// if (!child.hasEnded()) return true;
	// }
	// }
	// return false;
	// }
	
	// // log convenience methods //////////////////////////////////////////////////
	
	/**
	 * convenience method for adding a process log.
	 *
	 * @todo implement
	 */
	public function addLog(/*ProcessLog */ $processLog) {
		$this->log->info("EXEC TOKEN::addLog([{$processLog}])");
		return;
		// /**@var LoggingInstance **/
		// $loggingInstance = $this->processInstance->getLoggingInstance();
		// if (loggingInstance != null) {
		// $processLog->setToken($this);
		// $loggingInstance->addLog($processLog);
		// }
	}
	
	// /**
	// * convenience method for starting a composite log. When you add composite logs, make sure you
	// * put the {@link #endCompositeLog()} in a finally block.
	// */
	// public void startCompositeLog(CompositeLog compositeLog) {
	// LoggingInstance loggingInstance = processInstance.getLoggingInstance();
	// if (loggingInstance != null) {
	// compositeLog.setToken(this);
	// loggingInstance.startCompositeLog(compositeLog);
	// }
	// }
	
	// /**
	// * convenience method for ending a composite log. Make sure you put this in a finally block.
	// */
	// public void endCompositeLog() {
	// LoggingInstance loggingInstance = processInstance.getLoggingInstance();
	// if (loggingInstance != null) loggingInstance.endCompositeLog();
	// }
	
	// // various information extraction methods ///////////////////////////////////
	public function hasEnded() {
		return !is_null($this->end);
	}
	public function isRoot() {
		return (!is_null($this->processInstance) && $this->equals($this->processInstance->getRootToken()));
	}
	public function hasParent() {
		return !is_null($this->parent);
	}
	public function hasChild($name) {
		return $this->children->containsKey($name);
	}
	
	/**
	 *
	 * @param string $name
	 *        	Name of child token
	 * @return Token
	 */
	public function getChild($name) {
		return $this->children->get($name);
	}
	public function getFullName() {
		$this->log->debug("getFullName of [{$this->name}]");
		if ($this->isRoot()) {
			return "/";
		}
		
		$name = "";
		for($token = $this; $token->hasParent(); $token = $token->getParent()) {
			$tokenName = $token->getName();
			$name = "/" . $tokenName . $name;
		}
		return $name;
	}
	
	// public List getChildrenAtNode(Node aNode) {
	// List foundChildren = new ArrayList();
	// getChildrenAtNode(aNode, foundChildren);
	// return foundChildren;
	// }
	
	// private void getChildrenAtNode(Node aNode, List foundTokens) {
	// if (aNode.equals(node)) {
	// foundTokens.add(this);
	// }
	// else if (children != null) {
	// for (Iterator it = children.values().iterator(); it.hasNext();) {
	// Token child = (Token) it.next();
	// child.getChildrenAtNode(aNode, foundTokens);
	// }
	// }
	// }
	
	// public void collectChildrenRecursively(List tokens) {
	// if (children != null) {
	// for (Iterator iter = children.values().iterator(); iter.hasNext();) {
	// Token child = (Token) iter.next();
	// tokens.add(child);
	// child.collectChildrenRecursively(tokens);
	// }
	// }
	// }
	
	/**
	 *
	 * @param string $relativeTokenPath        	
	 * @return Token
	 */
	public function findToken($relativeTokenPath) {
		$this->log->debug("findToken($relativeTokenPath)");
		if (is_null($relativeTokenPath))
			return null;
		
		$path = trim($relativeTokenPath);
		if (strlen($path) == 0 || $path == ".")
			return $this;
		if ($path === "..")
			return $this->parent;
		
		if (substr($path, 0, 1) == "/") {
			$this->log->debug("\t LEADING /");
			return $this->processInstance->getRootToken()->findToken(substr($path, 1));
		}
		if (substr($path, 0, 2) == "./")
			return $this->findToken(substr($path, 2));
		if (substr($path, 0, 3) == "../") {
			return !is_null($this->parent) ? $this->parent->findToken(substr($path, 3)) : null;
		}
		
		if (sizeof($this->children) == 0)
			return null;
		
		$slashIndex = strpos($path, '/');
		if ($slashIndex === false)
			return $this->children->get($path);
		
		$token = $this->children->get(substr($path, 0, $slashIndex));
		return !is_null($token) ? $token->findToken(substr($path, $slashIndex + 1)) : null;
	}
	
	// public Map getActiveChildren() {
	// Map activeChildren = new HashMap();
	// if (children != null) {
	// for (Iterator iter = children.entrySet().iterator(); iter.hasNext();) {
	// Map.Entry entry = (Map.Entry) iter.next();
	// Token child = (Token) entry.getValue();
	// if (!child.hasEnded()) {
	// String childName = (String) entry.getKey();
	// activeChildren.put(childName, child);
	// }
	// }
	// }
	// return activeChildren;
	// }
	public function checkImplicitTermination() {
		if ($this->isTerminationImplicit && $this->node->hasNoLeavingTransitions()) {
			$this->end();
			if ($this->processInstance->isTerminatedImplicitly()) {
				$this->processInstance->end();
			}
		}
	}
	
	// public boolean isTerminatedImplicitly() {
	// if (end != null) return true;
	
	// Map leavingTransitions = node.getLeavingTransitionsMap();
	// if (leavingTransitions != null && !leavingTransitions.isEmpty()) {
	// // ok: found a non-terminated token
	// return false;
	// }
	
	// // loop over all active child tokens
	// for (Iterator iter = getActiveChildren().values().iterator(); iter.hasNext();) {
	// Token child = (Token) iter.next();
	// if (!child.isTerminatedImplicitly()) return false;
	// }
	// // if none of the above, this token is terminated implicitly
	// return true;
	// }
	
	// public int nextLogIndex() {
	// return nextLogIndex++;
	// }
	
	// /**
	// * suspends a process execution.
	// */
	// public void suspend() {
	// isSuspended = true;
	
	// suspendJobs();
	// suspendTaskInstances();
	
	// // propagate to child tokens
	// if (children != null) {
	// for (Iterator iter = children.values().iterator(); iter.hasNext();) {
	// Token child = (Token) iter.next();
	// child.suspend();
	// }
	// }
	// }
	
	// private void suspendJobs() {
	// JbpmContext jbpmContext = JbpmContext.getCurrentJbpmContext();
	// if (jbpmContext != null) {
	// JobSession jobSession = jbpmContext.getJobSession();
	// if (jobSession != null) jobSession.suspendJobs(this);
	// }
	// }
	
	// private void suspendTaskInstances() {
	// TaskMgmtInstance taskMgmtInstance = processInstance.getTaskMgmtInstance();
	// if (taskMgmtInstance != null) taskMgmtInstance.suspend(this);
	// }
	
	// /**
	// * resumes a process execution.
	// */
	// public void resume() {
	// isSuspended = false;
	
	// resumeJobs();
	// resumeTaskInstances();
	
	// // propagate to child tokens
	// if (children != null) {
	// for (Iterator iter = children.values().iterator(); iter.hasNext();) {
	// Token child = (Token) iter.next();
	// child.resume();
	// }
	// }
	// }
	
	// private void resumeJobs() {
	// JbpmContext jbpmContext = JbpmContext.getCurrentJbpmContext();
	// if (jbpmContext != null) {
	// JobSession jobSession = jbpmContext.getJobSession();
	// if (jobSession != null) jobSession.resumeJobs(this);
	// }
	// }
	
	// private void resumeTaskInstances() {
	// TaskMgmtInstance taskMgmtInstance = processInstance.getTaskMgmtInstance();
	// if (taskMgmtInstance != null) taskMgmtInstance.resume(this);
	// }
	
	// // equals ///////////////////////////////////////////////////////////////////
	public function equals($o) {
		if ($o == $this)
			return true;
		if (!($o instanceof Token))
			return false;
		
		if ($this->id != 0 && $this->id == $o->getId())
			return true;
		/**@var Token **/
		$other = $o;
		return ($this->name != "" ? $this->name === $other->getName() : $other->getName() == null) && (!is_null($this->parent) ? $this->parent->equals($other->getParent()) : $this->processInstance->equals($other->getProcessInstance()));
	}
	
	// public int hashCode() {
	// int result = 2080763213 + (name != null ? name.hashCode() : 0);
	// result = 1076685199 * result
	// + (parent != null ? parent.hashCode() : processInstance.hashCode());
	// return result;
	// }
	public function __toString() {
		return "Token(" . ($this->id != 0 ? $this->id : $this->getFullName()) . ')';
	}
	
	/**
	 *
	 * @return ProcessInstance
	 */
	public function createSubProcessInstance(ProcessDefinition $subProcessDefinition) {
		// create the new sub process instance
		$subProcessInstance = new ProcessInstance($subProcessDefinition);
		// bind the subprocess to the super-process-token
		$this->setSubProcessInstance($subProcessInstance);
		$this->subProcessInstance->setSuperProcessToken($this);
		// make sure the process gets saved during super process save
		$this->processInstance->addCascadeProcessInstance($subProcessInstance);
		return $subProcessInstance;
	}
	
	/**
	 * locks a process instance for further execution.
	 * A locked token cannot continue execution.
	 * This is a non-persistent operation. This is used to prevent tokens being propagated during
	 * the execution of actions.
	 *
	 * @see #unlock(String)
	 */
	public function lock($lockOwner = "") {
		$this->log->debug("[{$lockOwner}] attempting to lock [{$this}] (lock is: [{$this->lock}])");
		
		if ($lockOwner == "")
			throw new pBpmException("lock owner is null");
		
		if ($this->lock == "") {
			$this->lock = $lockOwner;
			$this->log->debug("[{$lockOwner}] locked [{$this}]");
		} else if ($this->lock != $lockOwner) {
			throw new pBpmException("[{$lockOwner}] cannot lock [{$this}] because [{$this->lock}] already locked it");
		}
	}
	
	/**
	 *
	 * @see #lock(String)
	 */
	public function unlock($lockOwner) {
		if (!is_null($this->lock)) {
			if ($this->lock != $lockOwner) {
				throw new pBpmException("[{$lockOwner}] cannot unlock [{$this}] because [{$this->lock}] locked it");
			}
			
			$this->lock = null;
			$this->log->debug("[{$lockOwner}] unlocked [{$this}]");
		} else {
			$this->log->warn("[{$this}] was already unlocked");
		}
	}
	
	// /**
	// * force unlocking the token, even if the owner is not known. In some use cases (e.g. in the
	// * jbpm esb integration) the lock is persistent, so a state can be reached where the client
	// * needs a possibility to force unlock of a token without knowing the owner.
	// *
	// * @see <a href="https://jira.jboss.org/jira/browse/JBPM-1888">JBPM-1888</a>
	// * @deprecated Use {@link #forceUnlock()} instead
	// */
	// public void foreUnlock() {
	// forceUnlock();
	// }
	
	// /**
	// * force unlocking the token, even if the owner is not known. In some use cases (e.g. in the
	// * jbpm esb integration) the lock is persistent, so a state can be reached where the client
	// * needs a possibility to force unlock of a token without knowing the owner.
	// *
	// * @see <a href="https://jira.jboss.org/jira/browse/JBPM-1888">JBPM-1888</a>
	// */
	// public void forceUnlock() {
	// if (lock != null) {
	// lock = null;
	// if (log.isDebugEnabled()) log.debug("forcefully unlocked " + this);
	// }
	// else {
	// log.warn(this + " was unlocked already");
	// }
	// }
	
	/**
	 * return the current lock owner of the token
	 *
	 * @see <a href="https://jira.jboss.org/jira/browse/JBPM-1888">JBPM-1888</a>
	 */
	public function getLockOwner() {
		return $this->lock;
	}
	public function isLocked() {
		return $this->lock != "";
	}
	
	// // getters and setters //////////////////////////////////////////////////////
	public function getId() {
		return $this->id;
	}
	public function getStart() {
		return $this->start;
	}
	public function getEnd() {
		return $this->end;
	}
	public function setName($name) {
		$this->name = $name;
	}
	public function getName() {
		return $this->name;
	}
	public function getProcessInstance() {
		return $this->processInstance;
	}
	public function getChildren() {
		return $this->children;
	}
	
	/**
	 *
	 * @return Node
	 */
	public function getNode() {
		return $this->node;
	}
	public function setNode(Node $node = null) {
		$this->log->debug("setNode([{$node}])");
		$this->node = $node;
	}
	public function getParent() {
		return $this->parent;
	}
	public function stParent(Token $parent) {
		$this->parent = $parent;
	}
	public function setProcessInstance(ProcessInstance $processInstance) {
		$this->processInstance = $processInstance;
	}
	public function getSubProcessInstance() {
		return $this->subProcessInstance;
	}
	public function getNodeEnter() {
		return $this->nodeEnter;
	}
	public function setNodeEnter(\DateTime $nodeEnter) {
		$this->nodeEnter = $nodeEnter;
	}
	public function isAbleToReactivateParent() {
		return $this->isAbleToReactivateParent;
	}
	public function setAbleToReactivateParent($isAbleToReactivateParent) {
		$this->isAbleToReactivateParent = $isAbleToReactivateParent;
	}
	public function isTerminationImplicit() {
		return $this->isTerminationImplicit;
	}
	public function setTerminationImplicit($isTerminationImplicit) {
		$this->isTerminationImplicit = $isTerminationImplicit;
	}
	public function isSuspended() {
		return $this->isSuspended;
	}
	public function setSubProcessInstance(ProcessInstance $subProcessInstance = null) {
		$this->subProcessInstance = $subProcessInstance;
	}
}
