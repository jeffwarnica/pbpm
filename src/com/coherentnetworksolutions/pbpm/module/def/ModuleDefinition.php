<?php
namespace com\coherentnetworksolutions\pbpm\module\def;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;

/** @Entity 
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 **/
abstract class ModuleDefinition {

    /**
     * @Id 
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     * @var int
     * */
    public $id;
    
    protected $name = null;

    /**
     * @ManyToOne(targetEntity="com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition", inversedBy="definitions")
     * @var ProcessDefinition
     */
    protected $processDefinition = null;

    /**
     * @retutn ModuleInstance
     */
    public abstract function createInstance();

    public function getName() {
        return $this->name;
    }

    /**
     * @return ProcessDefinition 
     */
    public function getProcessDefinition() {
        return $this->processDefinition;
    }

    public function setProcessDefinition(ProcessDefinition $processDefinition) {
        $this->processDefinition = $processDefinition;
    }
}
