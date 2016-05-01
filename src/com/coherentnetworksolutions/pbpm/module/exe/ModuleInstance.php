<?php
namespace com\coherentnetworksolutions\pbpm\module\exe;

use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;

/**
 * @entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 */
class ModuleInstance {
	
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @var int
	 *
	 */
	public $id;
	
	/**
	 * @Column(type="integer")
	 * 
	 * @var int
	 */
	protected $version = -1;
	
	/**
	 * @ManyToOne(targetEntity="\com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance",cascade={"persist"})
	 * 
	 * @var ProcessInstance
	 */
	protected $processInstance;
	
	// // equals ///////////////////////////////////////////////////////////////////
	
	// public boolean equals(Object o) {
	// if (this == o) return true;
	// if (!(o instanceof ModuleInstance)) return false;
	
	// ModuleInstance other = (ModuleInstance) o;
	// if (id != 0 && id == other.getId()) return true;
	
	// return getClass().getName().equals(other.getClass().getName())
	// && processInstance.equals(other.getProcessInstance());
	// }
	
	// public int hashCode() {
	// int result = 1849786963 + getClass().getName().hashCode();
	// result = 1566965963 * result + processInstance.hashCode();
	// return result;
	// }
	
	// protected Service getService(String serviceName) {
	// return Services.getCurrentService(serviceName, false);
	// }
	
	// // getters and setters //////////////////////////////////////////////////////
	
	// public long getId() {
	// return id;
	// }
	
	/**
	 *
	 * @return ProcessInstance
	 */
	public function getProcessInstance() {
		return $this->processInstance;
	}
	
	/**
	 *
	 * @param ProcessInstance $processInstance        	
	 */
	public function setProcessInstance(ProcessInstance $processInstance) {
		$this->processInstance = $processInstance;
	}
}
