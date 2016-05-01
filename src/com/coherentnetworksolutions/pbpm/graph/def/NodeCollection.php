<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;

/**
 * is a common supertype for a ProcessDefinition and a SuperState.
 */
interface NodeCollection  {

    /**
     * is the ordered list of nodes.
     * @return \SplDoublyLinkedList
     */
    function getNodes();

    /**
     * maps node-names to nodes.  returns an empty map if
     * no nodes are contained.
     * @return array
    */
    function getNodesMap();

    /**
     * retrieves a node by name.
     * @param String node name
     * @return Node the node or null if no such node is present.
    */
    function getNode($name);

    /**
     * is true if this node-collection contains a node with the
     * given name, false otherwise.
     * @return boolean
    */
    function hasNode($name);

    /**
     * adds the given node to this node-collection.
     * @param Node
     * @return Node the added node.
     * @throws IllegalArgumentException if node is null.
    */
    function addNode(Node $node);

    /**
     * removes the given node from this node-collection.
     * @param Node
     * @return Node the removed node or null if the node was not present in this collection.
     * @throws IllegalArgumentException if node is null or if the node is not contained in this nodecollection.
    */
    function removeNode(Node $node);

    /**
     * changes the order of the nodes : the node on oldIndex
     * is removed and inserted in the newIndex. All nodes inbetween
     * the old and the new index shift one index position.
     * @param integer old location
     * @param integer new location
     * @throws IndexOutOfBoundsException
     * @todo ?????????
    */
    
//     function reorderNode($oldIndex, $newIndex);

    /**
     * generates a new name for a node to be added to this collection.
    */
    //@todo ??? Or useless?
//     function generateNodeName();

    /**
     * finds the node by the given hierarchical name.  use .. for
     * the parent, use slashes '/' to separate the node names.
    */
    function findNode($hierarchicalName);
}
