<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\graph\def\Action;
use com\coherentnetworksolutions\pbpm\graph\node\StartState;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use com\coherentnetworksolutions\pbpm\module\def\ModuleDefinition;
use com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskMgmtDefinition;
use com\coherentnetworksolutions\pbpm\graph\def\NodeCollection;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;

/**
 * @Entity *
 */
class ProcessDefinition extends GraphElement implements NodeCollection {
	
	/**
	 * @Column(type="integer")
	 *
	 * @var int
	 *
	 */
	protected $version = -1;
	
	/**
	 * @Column(type="boolean")
	 *
	 * @var boolean
	 */
	protected $isTerminationImplicit = false;
	
	/**
	 * @OneToOne(targetEntity="Node",cascade={"persist"})
	 *
	 * @var StartState
	 */
	protected $startState = null;
	
	/**
	 * @OneToMany(targetEntity="Node", mappedBy="processDefinition",cascade={"persist"})
	 *
	 * @var ArrayCollection
	 */
	protected $nodes;
	
	// /**
	// *
	// * @var array of Node, keyed on name
	// */
	// protected $nodesMap;
	
	/**
	 * @OneToMany(targetEntity="Action", mappedBy="processDefinition",cascade={"persist"}))
	 *
	 * @var ArrayCollection
	 */
	protected $actions;
	
	/**
	 * @OneToMany(targetEntity="com\coherentnetworksolutions\pbpm\module\def\ModuleDefinition", mappedBy="processDefinition",cascade={"persist"})
	 *
	 * @var ArrayCollection
	 */
	protected $definitions;
	
	// @formatter:off
	// event types //////////////////////////////////////////////////////////////
	public static $supportedEventTypes = [ 
			Event::EVENTTYPE_PROCESS_START,
			Event::EVENTTYPE_PROCESS_END,
			Event::EVENTTYPE_NODE_ENTER,
			Event::EVENTTYPE_NODE_LEAVE,
			Event::EVENTTYPE_TASK_CREATE,
			Event::EVENTTYPE_TASK_ASSIGN,
			Event::EVENTTYPE_TASK_START,
			Event::EVENTTYPE_TASK_END,
			Event::EVENTTYPE_TRANSITION,
			Event::EVENTTYPE_BEFORE_SIGNAL,
			Event::EVENTTYPE_AFTER_SIGNAL,
			Event::EVENTTYPE_SUPERSTATE_ENTER,
			Event::EVENTTYPE_SUPERSTATE_LEAVE,
			Event::EVENTTYPE_SUBPROCESS_CREATED,
			Event::EVENTTYPE_SUBPROCESS_END,
			Event::EVENTTYPE_TIMER 
	];
	// @formatter:on
	public function getSupportedEventTypes() {
		return self::$supportedEventTypes;
	}
	
	// // constructors /////////////////////////////////////////////////////////////
	public function __construct($nameOrNodes = null, $transitions = null) {
		$this->nodes = new ArrayCollection();
		$this->actions = new ArrayCollection();
		$this->definitions = new ArrayCollection();
		
		if (!is_array($nameOrNodes)){
			$this->processDefinition = $this;
			$this->name = $nameOrNodes;
			parent::__construct($nameOrNodes);
		}else{
			throw new \Exception("unsupported");
			// if (is_array($transitions)) {
			// ProcessFactory::addNodesAndTransitions($this, $nameOrNodes, $transitions);
			// }
		}
	}
	
	// public ProcessDefinition() {
	// this.processDefinition = this;
	// }
	public static function createNewProcessDefinition() {
		/**
		 *
		 * @var ProcessDefinition
		 */
		$processDefinition = new ProcessDefinition();
		$processDefinition->addDefinition(new TaskMgmtDefinition());
		
		// // now add all the default modules that are configured in the file jbpm.default.modules
		// String resource = JbpmConfiguration.Configs.getString("resource.default.modules");
		// Properties defaultModulesProperties = ClassLoaderUtil.getProperties(resource);
		// Iterator iter = defaultModulesProperties.keySet().iterator();
		// while (iter.hasNext()) {
		// String moduleClassName = (String) iter.next();
		// try {
		// ModuleDefinition moduleDefinition = (ModuleDefinition) ClassLoaderUtil.loadClass(moduleClassName).newInstance();
		// processDefinition.addDefinition(moduleDefinition);
		
		// } catch (Exception e) {
		// e.printStackTrace();
		// throw new JbpmException("couldn't instantiate default module '"+moduleClassName+"'", e);
		// }
		// }
		return $processDefinition;
	}
	public function createProcessInstance() {
		return new ProcessInstance($this);
	}
	
	// public void setProcessDefinition(ProcessDefinition processDefinition) {
	// if (! this.equals(processDefinition)) {
	// throw new JbpmException("can't set the process-definition-property of a process defition to something else then a self-reference");
	// }
	// }
	
	
	/**
	 * Tells whether this process definition is equal to the given object. This method considers
	 * two process definitions equal if they are equal in name and version, the name is not null
	 * and the version is not negative.
	 */
	public function __equals($o) {
		if ($o == $this) return true;
		if (!($o instanceof ProcessDefinition)) return false;
	
		if ($this->id != 0 && $this->id == $o->getId()) return true;
	
		return (!is_null($this->name) && $this->version >= 0 && $this->name === $o->getName()) && $this->version == $o->getVersion();
	}
	
	
	// parsing //////////////////////////////////////////////////////////////////
	
	/**
	 * parse a process definition from an xml string.
	 *
	 * @throws org.jbpm.jpdl.JpdlException if parsing reported an error.
	 * @return ProcessDefinition
	 */
	public static function parseXmlString($xml) {
		// StringReader stringReader = new StringReader(xml);
		$jpdlReader = new JpdlXmlReader($xml);
		return $jpdlReader->readProcessDefinition();
	}
	
	// /**
	// * parse a process definition from an xml resource file.
	// * @throws org.jbpm.jpdl.JpdlException if parsing reported an error.
	// */
	// public static ProcessDefinition parseXmlResource(String xmlResource) {
	// InputStream resourceStream = ClassLoaderUtil.getStream(xmlResource);
	// try {
	// return parseXmlInputStream(resourceStream);
	// }
	// finally {
	// if (resourceStream != null) {
	// try {
	// resourceStream.close();
	// }
	// catch (IOException e) {
	// }
	// }
	// }
	// }
	
	// /**
	// * parse a process definition from an xml input stream.
	// * @throws org.jbpm.jpdl.JpdlException if parsing reported an error.
	// */
	// public static ProcessDefinition parseXmlInputStream(InputStream inputStream) {
	// JpdlXmlReader jpdlReader = new JpdlXmlReader(new InputSource(inputStream));
	// return jpdlReader.readProcessDefinition();
	// }
	
	// /**
	// * parse a process definition from an xml reader.
	// * @throws org.jbpm.jpdl.JpdlException if parsing reported an error.
	// */
	// public static ProcessDefinition parseXmlReader(Reader reader) {
	// JpdlXmlReader jpdlReader = new JpdlXmlReader(new InputSource(reader));
	// return jpdlReader.readProcessDefinition();
	// }
	
	// /**
	// * parse a process definition from a process archive zip-stream.
	// * @throws org.jbpm.jpdl.JpdlException if parsing reported an error.
	// */
	// public static ProcessDefinition parseParZipInputStream(ZipInputStream zipInputStream) {
	// try {
	// return new ProcessArchive(zipInputStream).parseProcessDefinition();
	// } catch (IOException e) {
	// throw new JbpmException("couldn't parse process zip file zipInputStream", e);
	// }
	// }
	
	// /**
	// * parse a process definition from a process archive resource.
	// * @throws org.jbpm.jpdl.JpdlException if parsing reported an error.
	// */
	// public static ProcessDefinition parseParResource(String parResource) {
	// return parseParZipInputStream(new ZipInputStream(ClassLoaderUtil.getStream(parResource)));
	// }
	
	// nodes ////////////////////////////////////////////////////////////////////
	
	// javadoc description in NodeCollection
	public function getNodes() {
		return $this->nodes;
	}
	public function getNodesMap() {
		$nodesMap = array ();
		if (sizeof($this->nodes)){
			foreach ($this->nodes as $node){
				$this->nodesMap[$node->getName()] = $node;
			}
		}
		return $nodesMap;
	}
	
	/**
	 *
	 * @return Node
	 * @see \com\coherentnetworksolutions\pbpm\graph\def\NodeCollection::getNode()
	 */
	public function getNode($name) {
		if (sizeof($this->nodesMap) == 0){
			return null;
		}
		if ($this->hasNode($name)){
			return $this->nodesMap[$name];
		}
		return null;
	}
	
	/**
	 *
	 * @return bool
	 */
	public function hasNode($name) {
		if (sizeof($this->nodesMap) == 0){
			return false;
		}
		return array_key_exists($name, $this->nodesMap);
	}
	
	// // javadoc description in NodeCollection
	public function addNode(Node $node = null) {
		if (is_null($node)){
			throw new \Exception("can't add a null node to a processdefinition");
		}
		$this->log->debug(">addNode([{$node->getName()}])");
		$this->nodes->add($node);
		$this->nodesMap[$node->getName()] = $node;
		$node->processDefinition = $this;
		
		if ($node instanceof StartState){
			$this->startState = $node;
		}
		return $node;
	}
	
	// // javadoc description in NodeCollection
	public function removeNode(Node $node = null) {
		/**
		 *
		 * @var Node
		 */
		$removedNode = null;
		if (is_null($node))
			throw new \Exception("can't remove a null node from a process definition");
		$this->nodes->removeElement($node);
		unset($this->nodesMaps[$node->getName()]);
		
		if ($this->startState == $removedNode){
			$this->startState = null;
		}
		
		return $removedNode;
	}
	public function updateNodeName(Node $node, $newName) {
		$this->log->debug("updateNodeName([{$node->getName()}], [{$newName}]");
		unset($this->nodesMap[$node->getName()]);
		$this->nodesMap[$newName] = $node;
	}
	
	// // javadoc description in NodeCollection
	// public void reorderNode(int oldIndex, int newIndex) {
	// if ( (nodes!=null)
	// && (Math.min(oldIndex, newIndex)>=0)
	// && (Math.max(oldIndex, newIndex)<nodes.size()) ) {
	// Object o = nodes.remove(oldIndex);
	// nodes.add(newIndex, o);
	// } else {
	// throw new IndexOutOfBoundsException("couldn't reorder element from index '"+oldIndex+"' to index '"+newIndex+"' in nodeList '"+nodes+"'");
	// }
	// }
	
	// // javadoc description in NodeCollection
	// public String generateNodeName() {
	// return generateNodeName(nodes);
	// }
	
	// // javadoc description in NodeCollection
	// public Node findNode(String hierarchicalName) {
	// return findNode(this, hierarchicalName);
	// }
	
	// public static String generateNodeName(List nodes) {
	// String name = null;
	// if (nodes==null) {
	// name = "1";
	// } else {
	// int n = 1;
	// while (containsName(nodes, Integer.toString(n))) n++;
	// name = Integer.toString(n);
	// }
	// return name;
	// }
	
	// static boolean containsName(List nodes, String name) {
	// Iterator iter = nodes.iterator();
	// while (iter.hasNext()) {
	// Node node = (Node) iter.next();
	// if ( name.equals(node.getName()) ) {
	// return true;
	// }
	// }
	// return false;
	// }
	public function findNode($hierarchicalName = "") {
		$this->log->debug("PD->findNode([$hierarchicalName]");
		return $this->findNodeFromCollection($this, $hierarchicalName);
	}
	public function findNodeFromCollection(NodeCollection $nodeCollection, $hierarchicalName) {
		$this->log->debug("findNodeFromCollection[{$nodeCollection->getName()}], [{$hierarchicalName}]");
		
		$nameParts = explode("/", $hierarchicalName);
		$this->log->debug("\tnameParts:" . join("||", $nameParts));
		
		// Simplest case. One element, return what we can find.
		if (sizeof($nameParts) == 1){
			$this->log->debug("\tnameParts == 1. Simple return");
			$nodeName = $nameParts[0];
			return $nodeCollection->getNode($nodeName);
		}
		
		/**
		 *
		 * @var GraphElement $currentElement
		 */
		$currentElement = $nodeCollection;
		$startIndex = 0;
		if (strlen($nameParts[0]) == 0){
			$this->log->debug("\tnameParts[0] is length 0... currentElement will be PD, start index will be 1");
			// hierarchical name started with a '/'
			$currentElement = $currentElement->getProcessDefinition();
			$startIndex = 1;
		}
		
		for($i = $startIndex; $i < sizeof($nameParts); $i++){
			$namePart = $nameParts[$i];
			$this->log->debug("\ti: $i Working on namePart: $namePart");
			$this->log->debug("\t\tCurrentElement is: [" . (is_null($currentElement) ? "NULL" : $currentElement->getName()) . "]");
			if ($namePart == ".."){
				$this->log->debug("\t\t\tnamePart is [..]");
				// namePart calls for parent, but current element is absent
				if (is_null($currentElement)){
					$this->log->debug("namePart calls for parent (..), but current element is null, returning null");
					return null;
				}
				$this->log->debug("\t\tSetting currentElement to parent");
				$currentElement = $currentElement->getParent();
			}else{
				// namePart calls for child, but current element is not a collection
				if (!($currentElement instanceof NodeCollection)) {
					$this->log->debug("\tCurrentElement isnt a NodeCollection. Returning NULL (but get_class() says: " . (is_null($currentElement) ? "NULL" : get_class($currentElement)));
						return null;
				}
				$this->log->debug("\tCurrentElement is a NodeCollection [{$currentElement->getName()}]. Setting currentCollection to currentElement, getting new currentElement from it");
				$currentCollection = $currentElement;
				$currentElement = $currentCollection->getNode($namePart);
// 				$this->log->debug("\t\tCurrentElement is now []");
			}
			$this->log->debug("\t\t<loop>");
		}
		$this->log->debug("\tGOT THIS FAR. currentElement thinks its name is [" . (is_null($currentElement) ? "NULL" : $currentElement->getName()) . "]");
		// current element could be the process definition or might be absent
		if ($currentElement instanceof Node){
			$this->log->debug("\t\t\tWhich I think is a Node(ish). And will return it.");
			return $currentElement;
		}else{
			$this->log->debug("currentElement isn't a Node, so return null (But get_class says: [" . get_class($currentElement) . "]");
			return null;
		}
	}
	
	// public void setStartState(StartState startState) {
	// if ( (this.startState!=startState)
	// && (this.startState!=null) ){
	// removeNode(this.startState);
	// }
	// this.startState = startState;
	// if (startState!=null) {
	// addNode(startState);
	// }
	// }
	
	/**
	 *
	 * @return GraphElement
	 */
	public function getParent() {
		return null;
	}
	
	// actions //////////////////////////////////////////////////////////////////
	
	/**
	 * creates a bidirectional relation between this process definition and the given action.
	 *
	 * @throws IllegalArgumentException if action is null or if action.getName() is null.
	 */
	public function addAction(Action $action) {
		if (is_null($action))
			throw new \Exception("can't add a null action to an process definition");
		if ($action->getName() == null)
			throw new \Exception("can't add an unnamed action to an process definition");
		$this->actions->set($action->getName(), $action);
		$action->setProcessDefinition($this);
		return $action;
	}
	
	// /**
	// * removes the bidirectional relation between this process definition and the given action.
	// * @throws IllegalArgumentException if action is null or if the action was not present in the actions of this process definition.
	// */
	// public void removeAction(Action action) {
	// if (action == null) throw new IllegalArgumentException("can't remove a null action from an process definition");
	// if (actions != null) {
	// if (! actions.containsValue(action)) {
	// throw new IllegalArgumentException("can't remove an action that is not part of this process definition");
	// }
	// actions.remove(action.getName());
	// action.processDefinition = null;
	// }
	// }
	
	/**
	 *
	 * @param unknown $name        	
	 * @return Action
	 */
	public function getAction($name) {
		return $this->actions->get($name);
	}
	
	/**
	 *
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getActions() {
		return $this->actions;
	}
	public function hasActions() {
		return (sizeof($this->actions) > 0);
	}
	
	// // module definitions ///////////////////////////////////////////////////////
	
	// public Object createInstance() {
	// return new ProcessInstance(this);
	// }
	public function addDefinition(ModuleDefinition $moduleDefinition) {
		$this->definitions->set(get_class($moduleDefinition), $moduleDefinition);
		$moduleDefinition->setProcessDefinition($this);
		return $moduleDefinition;
	}
	
	// public ModuleDefinition removeDefinition(ModuleDefinition moduleDefinition) {
	// ModuleDefinition removedDefinition = null;
	// if (moduleDefinition == null) throw new IllegalArgumentException("can't remove a null moduleDefinition from a process definition");
	// if (definitions != null) {
	// removedDefinition = (ModuleDefinition) definitions.remove(moduleDefinition.getClass().getName());
	// if (removedDefinition!=null) {
	// moduleDefinition.setProcessDefinition(null);
	// }
	// }
	// return removedDefinition;
	// }
	public function getDefinition($clazz) {
		return $this->definitions->get($clazz);
	}
	public function getContextDefinition() {
		return $this->getDefinition('com\coherentnetworksolutions\pbpm\context\def\ContextDefinition');
	}
	
	// public FileDefinition getFileDefinition() {
	// return (FileDefinition) getDefinition(FileDefinition.class);
	// }
	/**
	 *
	 * @return TaskMgmtDefinition
	 */
	public function getTaskMgmtDefinition() {
		return $this->getDefinition('com\coherentnetworksolutions\pbpm\taskmgmt\def\TaskMgmtDefinition');
	}
	/**
	 * @return ArrayCollection
	 */
	public function getDefinitions() {
		return $this->definitions;
	}
	
	// public void setDefinitions(Map definitions) {
	// this.definitions = definitions;
	// }
	public function getVersion() {
		return $this->version;
	}
	public function setVersion($version) {
		$this->version = intval($version);
	}
	
	/**
	 *
	 * @return StartState
	 */
	public function getStartState() {
		return $this->startState;
	}
	public function setStartState(Node $startState) {
		$this->startState = $startState;
	}
	public function isTerminationImplicit() {
		return $this->isTerminationImplicit;
	}
	public function setTerminationImplicit($isTerminationImplicit) {
		$this->isTerminationImplicit = boolval($isTerminationImplicit);
	}
}