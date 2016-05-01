<?php

namespace com\coherentnetworksolutions\pbpm\graph\def;

use Doctrine\Common\Collections\ArrayCollection;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;

/**
 * brings hierarchy into the elements of a process definition by creating a
 * parent-child relation between {@link GraphElement}s.
 *
 * @Entity
 */
class SuperState extends Node implements NodeCollection {
	
	/**
	 * @OneToMany(targetEntity="Node", mappedBy="processDefinition",cascade={"persist"})
	 * 
	 * @var ArrayCollection
	 */
	protected $nodes;
	
	// /**
	// * @var array of Node, keyed on name
	// */
	// protected $nodesMap;
	
	// @formatter:off
	// event types //////////////////////////////////////////////////////////////
	public static $supportedEventTypes = [ 
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
	public function __construct($name = null) {
		$this->nodes = new ArrayCollection();
		parent::__construct($name);
	}
	
	// // xml //////////////////////////////////////////////////////////////////////
	public function read(\DOMElement $element, JpdlXmlReader $jpdlXmlReader) {
		$jpdlXmlReader->readNodes($element, $this);
	}
	
	// // behaviour ////////////////////////////////////////////////////////////////
	
	// public void execute(ExecutionContext executionContext) {
	// if ((nodes == null) || (nodes.size() == 0)) {
	// throw new JbpmException("transition enters superstate +" + this
	// + "' and it there is no first child-node to delegate to");
	// }
	// Node startNode = (Node) nodes.get(0);
	// startNode.enter(executionContext);
	// }
	
	// // nodes ////////////////////////////////////////////////////////////////////
	
	/**
	 *
	 * @return ArrayCollection
	 */
	public function getNodes() {
		return $this->nodes;
	}
	
	/**
	 *
	 * @return Node
	 * @see \com\coherentnetworksolutions\pbpm\graph\def\NodeCollection::getNode()
	 */
	public function getNode($name) {
		$this->log->debug("ss->getNode({$name})");
		$map = $this->getNodesMap();
		
		if (sizeof($map) == 0 || !array_key_exists($name, $map)){
			$this->log->debug("\t<NULL");
			return null;
		}
		return $map[$name];
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
	public function addNode(Node $node = null) {
		if (is_null($node))
			throw new \Exception("can't add a null node to a SuperState");
		
		$this->nodes->add($node);
		$this->nodesMap[$node->getName()] = $node;
		$this->log->debug("Setting [{$node->getName()}]->superState to {$this->getName()}");
		$node->superState = $this;
		
		return $node;
	}
	public function removeNode(Node $node = null) {
		/**
		 *
		 * @var Node $removedNode
		 */
		$removedNode = null;
		if (is_null($node)){
			throw new \Exception("can't remove a null node from a superstate");
		}
		
		$this->nodes->removeElement($node);
		unset($this->nodesMaps[$node->getName()]);
		$removedNode = $node;
		$removedNode->superState = null;
		
		return $removedNode;
	}
	
	// // javadoc description in NodeCollection
	// public void reorderNode(int oldIndex, int newIndex) {
	// if ((nodes != null) && (Math.min(oldIndex, newIndex) >= 0)
	// && (Math.max(oldIndex, newIndex) < nodes.size())) {
	// Object o = nodes.remove(oldIndex);
	// nodes.add(newIndex, o);
	// }
	// else {
	// throw new IndexOutOfBoundsException("couldn't reorder element from index '" + oldIndex
	// + "' to index '" + newIndex + "' in nodeList '" + nodes + "'");
	// }
	// }
	
	// // javadoc description in NodeCollection
	// public String generateNodeName() {
	// return ProcessDefinition.generateNodeName(nodes);
	// }
	
	/**
	 *
	 * @return Node
	 */
	public function findNode($hierarchicalName) {
		$this->log->debug("SS::findNode($hierarchicalName)");
		return $this->processDefinition->findNodeFromCollection($this, $hierarchicalName);
	}
	
	/**
	* recursively checks if the given node is one of the descendants of this
	* supernode.
	* @return bool
	*/
	public function containsNode(Node $node) {
		$containsNode = false;
		$parent = $node->getSuperState();
		while ((!$containsNode) && (!is_null($parent))) {
			if ($this->__equals($parent)) {
				$containsNode = true;
			} else {
				$parent = $parent->getSuperState();
			}
		}
		return $containsNode;
	}
	
	// // other ////////////////////////////////////////////////////////////////////
	
	/**
	 *
	 * @return GraphElement
	 */
	public function getParent() {
		$parent = $this->processDefinition;
		if (!is_null($this->superState)){
			$parent = $this->superState;
		}
		return $parent;
	}
	public function isSuperStateNode() {
		return true;
	}
	public function getNodesMap() {
		$nodesMap = array ();
		foreach ($this->nodes as $node){
			$nodesMap[$node->getName()] = $node;
		}
		return $nodesMap;
	}
}