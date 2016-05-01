<?php

namespace com\coherentnetworksolutions\pbpm\graph\node;

use com\coherentnetworksolutions\pbpm\graph\def\Node;
use com\coherentnetworksolutions\pbpm\graph\def\Event;
use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\GraphElement;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\graph\exe\ExecutionContext;
use com\coherentnetworksolutions\pbpm\context\def\VariableAccess;
use com\coherentnetworksolutions\pbpm\pbpmContext;
use com\coherentnetworksolutions\pbpm\graph\def\Transition;
use com\coherentnetworksolutions\pbpm\graph\exe\Token;

/** @entity **/
class ProcessState extends Node {
	
	/**
	 *
	 * @var SubProcessResolver $defaultSubProcessResolver
	 */
	private $defaultSubProcessResolver;
	
	// /** @deprecated set configuration entry <code>jbpm.sub.process.resolver</code> instead */
	// public static void setDefaultSubProcessResolver(SubProcessResolver subProcessResolver) {
	// defaultSubProcessResolver = subProcessResolver;
	// }
	
	/**
	 *
	 * @return SubProcessResolver
	 */
	public static function getSubProcessResolver() {
		if (!is_null(self::defaultSubProcessResolver)){
			return self::defaultSubProcessResolver;
		}else{
			return new DbSubProcessResolver();
		}
	}
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\def\GraphElement")
	 *
	 * @var ProcessDefinition
	 */
	protected $subProcessDefinition;
	
	/**
	 * @OneToMany(targetEntity="\com\coherentnetworksolutions\pbpm\context\def\VariableAccess", mappedBy="ProcessState")
	 * @var ArrayCollection $variableAccesses
	 */
	protected $variableAccesses;
	
	/**
	 * @column(type="string", nullable=true);
	 * @var unknown $subProcessName
	 */
	protected $subProcessName;
	
	// public ProcessState() {
	// }
	
	public function __construct($name = null) {
		$this->variableAccesses = new ArrayCollection();
		parent::__construct($name);
	}
	
	// // event types //////////////////////////////////////////////////////////////
	
	public static $supportedEventTypes = [ 
		Event::EVENTTYPE_SUBPROCESS_CREATED,
		Event::EVENTTYPE_SUBPROCESS_END,
		Event::EVENTTYPE_NODE_ENTER,
		Event::EVENTTYPE_NODE_LEAVE,
		Event::EVENTTYPE_BEFORE_SIGNAL,
		Event::EVENTTYPE_AFTER_SIGNAL
	];
	
	// // xml //////////////////////////////////////////////////////////////////////
	
	public function read(\DOMElement $processStateElement, JpdlXmlReader $jpdlReader) {
		$subProcessElements = $jpdlReader->getDirectChildrenByTagName($processStateElement, "sub-process");
		if (sizeof($subProcessElements) >0) {
			$binding = strtolower($subProcessElements[0]->getAttribute("binding"));
			if ("late" === $binding) {
				$subProcessName = $subProcessElements[0]->getAttribute("name");
				$this->log->debug(this + " will be late bound to process definition: [{$subProcessName}]");
			} else {
				$this->subProcessDefinition = $this->resolveSubProcess($subProcessElements[0], $jpdlReader);
			}
		}
		$this->variableAccesses = $jpdlReader->readVariableAccesses($processStateElement);
	}
	
	private function resolveSubProcess(\DOMElement $subProcessElement, JpdlXmlReader $jpdlReader) {
		$subProcessResolver = $this->getSubProcessResolver();
		try {
			$subProcess = $subProcessResolver->findSubProcess(subProcessElement);
			if (!is_null($subProcess)) {
				$this->log->debug("bound [{$this}] to [{$subProcess}]");
				return $subProcess;
			}
		} catch (\Exception $e) {
			$jpdlReader->addError($e->getMessage());
		}
	
		// check whether this is a recursive process invocation
		$subProcessName = $subProcessElement->getAttribute("name");
		if ($subProcessName != "" && $subProcessName == $this->processDefinition->getName()) {
			$this->log->debug("bound [{$this}] to its own [{$this->processDefinition}]");
			return $this->processDefinition;
		}
		return null;
	}
	
	public function execute(ExecutionContext $executionContext) {
		$superProcessToken = $executionContext->getToken();
		
		$usedSubProcessDefinition = $this->subProcessDefinition;
		// if this process has late binding
		if (is_null($this->subProcessDefinition) && !is_null($this->subProcessName)) {
			throw new \Exception("We don't support late binding sub-processes yet");
// 			Element subProcessElement = new DefaultElement("sub-process");
// 			subProcessElement.addAttribute("name", (String) JbpmExpressionEvaluator
// 			.evaluate(subProcessName, executionContext, String.class));
			
// 			SubProcessResolver subProcessResolver = getSubProcessResolver();
// 			usedSubProcessDefinition = subProcessResolver.findSubProcess(subProcessElement);
		}
		
		// create the subprocess
		$subProcessInstance = $superProcessToken->createSubProcessInstance($usedSubProcessDefinition);
		
		// fire the subprocess created event
		$this->fireEvent(Event::EVENTTYPE_SUBPROCESS_CREATED, $executionContext);
		
		// feed the readable variableInstances
		if ($this->variableAccesses->count()>0) {
			$superContextInstance = $executionContext->getContextInstance();
			$subContextInstance = $subProcessInstance->getContextInstance();
			$subContextInstance->setTransientVariables($superContextInstance->getTransientVariables());
			
			// loop over all the variable accesses
			$iter = $this->variableAccesses->getIterator();
			while ($iter->valid()) {
				/**@var VariableAccess $variableAccess **/
				$variableAccess = $iter->current();
				// if this variable access is readable
				if ($variableAccess->isReadable()) {
					// the variable is copied from the super process variable name
					// to the sub process mapped name
					$variableName = $variableAccess-> getVariableName();
					$value = $superContextInstance->getVariable($variableName, $superProcessToken);
					if (!is_null($value)) {
						$mappedName = $variableAccess->getMappedName();
						$this->log->debug("{$superProcessToken} reads '{$variableName}' into '{$mappedName}");
						$subContextInstance->setVariable($mappedName, $value);
					}
				}
				$iter->next();
			}
		}
		
		// send the signal to start the subprocess
// 		$pbpmContext = pbpmContext::getCurrentContext();
// 		MessageService messageService;
// 		if (jbpmContext != null && Configs.getBoolean("jbpm.sub.process.async")
// 		&& (messageService = jbpmContext.getServices().getMessageService()) != null) {
// 		// signal sub-process token asynchronously to clearly denote transactional boundaries
// 		// https://jira.jboss.org/browse/JBPM-2948
// 		SignalTokenJob job = new SignalTokenJob(subProcessInstance.getRootToken());
// 		job.setDueDate(new Date());
// 		messageService.send(job);
// 		}
// 		else {
		// message service unavailable, signal sub-process synchronously
		$subProcessInstance->signal();
// 		}
	}
	
	public function leave(ExecutionContext $executionContext, $transitionNameOrTransition = "") {
		$subProcessInstance = $executionContext->getSubProcessInstance();
		$superProcessToken = $subProcessInstance->getSuperProcessToken();
		
		if ($this->variableAccesses->count()>0) {
			$superContextInstance = $executionContext->getContextInstance();
			$subContextInstance = $subProcessInstance->getContextInstance();
			$subContextInstance->setTransientVariables($superContextInstance->getTransientVariables());
			
		
			// loop over all the variable accesses
			$iter = $this->variableAccesses->getIterator();
			while ($iter->valid()) {
				/**@var VariableAccess $variableAccess **/
				$variableAccess = $iter->current();
				
				// if this variable access is writable
				if ($variableAccess->isWritable()) {
					// the variable is copied from the sub process mapped name
					// to the super process variable name
					$mappedName = $variableAccess->getMappedName();
					$value = $subContextInstance->getVariable($mappedName);
					if (!is_null($value )) {
						$variableName = variableAccess.getVariableName();
						$this->log->debug("{$superProcessToken} writes '{$variableName}' into '{$mappedName}");
						$superContextInstance->setVariable($variableName, $value, $superProcessToken);
					}
				}
			$iter->next();
			}
		}
		
		// fire the subprocess ended event
		$this->fireEvent(Event::EVENTTYPE_SUBPROCESS_END, $executionContext);
		
		// remove the subprocess reference
		$superProcessToken->setSubProcessInstance(null);
		
		// override the normal log generation in super.leave() by creating the log here
		// and replacing addNodeLog() with an empty version
// 		superProcessToken.addLog(new ProcessStateLog(this, superProcessToken.getNodeEnter(), Clock.getCurrentTime(), subProcessInstance));
		
		// call the subProcessEndAction
		parent::leave($executionContext, $this->getDefaultLeavingTransition());
	}
	
	protected function addNodeLog(Token $token) {
	// override the normal log generation in super.leave() by creating the log in this.leave()
	// and replacing this method with an empty version
	}
	
	public function getSubProcessDefinition() {
		return $this->subProcessDefinition;
	}
	
	public function setSubProcessDefinition(ProcessDefinition $subProcessDefinition) {
		$this->subProcessDefinition = $subProcessDefinition;
	}
	
}
